import assert from "node:assert/strict";
import { createHash, webcrypto } from "node:crypto";
import { readFileSync } from "node:fs";
import { resolve } from "node:path";
import { createContext, runInContext } from "node:vm";

const plugin = resolve(process.argv[2]);
const inline = readFileSync(process.argv[3], "utf8");
const asset = readFileSync(resolve(plugin, "assets/dist/debugbundle-browser.js"), "utf8");
const receipt = JSON.parse(readFileSync(resolve(plugin, "assets/dist/sdk-build.json"), "utf8"));
assert.equal(receipt.package, "@debugbundle/sdk-browser");
assert.match(receipt.version, /^3\./);
assert.equal(createHash("sha256").update(asset).digest("hex"), receipt.asset_sha256);
assert.doesNotMatch(inline, /ZIP_SERVER_SECRET|projectToken|project_token/);

async function check(withHook) {
  const listeners = new Map();
  const timers = new Map();
  const requests = [];
  let timerId = 0;
  const context = createContext({
    URL, URLSearchParams, TextEncoder, TextDecoder, Headers, AbortController,
    crypto: webcrypto, console,
    setTimeout(callback) { const id = ++timerId; timers.set(id, callback); return id; },
    clearTimeout(id) { timers.delete(id); },
    setInterval() { return ++timerId; }, clearInterval() {}, queueMicrotask,
    window: {
      addEventListener(name, callback) { listeners.set(name, callback); },
      removeEventListener(name) { listeners.delete(name); },
    },
    document: { readyState: "complete", visibilityState: "visible", referrer: "",
      addEventListener() {}, removeEventListener() {} },
    location: { href: "https://wordpress.example/checkout?token=ZIP_SOURCE_SECRET", pathname: "/checkout", search: "" },
    history: { pushState() {}, replaceState() {} },
    navigator: { userAgent: "WordPress ZIP smoke", language: "en" },
    fetch: async (url, init) => {
      const batch = JSON.parse(init.body).batch;
      requests.push({ url, headers: new Headers(init.headers), batch });
      return { ok: true, status: 202, headers: new Headers(), json: async () => ({ accepted: batch.length, rejected: 0, errors: [] }) };
    },
  });
  runInContext(inline, context, { timeout: 1000 });
  const config = context.window.DebugBundleWordPressConfig;
  assert.equal(config.endpoint, "/wp-json/debugbundle/v1/browser");
  assert.equal(config.service, "zip-smoke-browser");
  assert.equal(config.enabled, true);
  assert.equal(config.sessionSampleRate, 1);
  assert.equal(config.maxEventsPerSession, 100);
  assert.equal(config.networkFilter.urlDenyPatterns[0], config.endpoint);
  let hookCalls = 0;
  if (withHook) {
    // Exercise the optional entry-point debug handle and callback without
    // changing the unmodified PHP-generated configuration in the first run.
    config.debug = true;
    config.beforeSend = event => {
      hookCalls++;
      assert.doesNotMatch(JSON.stringify(event), /ZIP_SOURCE_SECRET/);
      if (event.payload.message === "drop this") return null;
      event.context = { marker: "zip-hook-kept", password: "ZIP_HOOK_SECRET" };
      return event;
    };
  }
  runInContext(asset, context, { timeout: 2000 });
  if (withHook) {
    const sdk = context.window.DebugBundleWordPress;
    assert.equal(typeof sdk?.captureLog, "function", "bundled entry did not initialize the SDK");
    sdk.captureLog("kept", "error", { password: "ZIP_SOURCE_SECRET" });
    sdk.captureLog("drop this", "error");
    assert.equal(hookCalls, 0, "capture called the deferred hook inline");
    await sdk.flush();
    sdk.dispose();
    assert.equal(hookCalls, 2);
  } else {
    assert.equal(typeof listeners.get("error"), "function", "bundled entry did not register automatic errors");
    runInContext('windowError = new Error("ZIP frontend failure");', context);
    listeners.get("error")({ error: context.windowError, message: "ZIP frontend failure" });
    assert.equal(requests.length, 0, "capture sent inline");
    // A deterministic timer pump exercises the real queued automatic flush;
    // the VM never makes a network request or waits on a real browser timer.
    for (let pass = 0; pass < 20 && requests.length === 0; pass++) {
      const ready = [...timers.values()];
      timers.clear();
      for (const callback of ready) callback();
      for (let turn = 0; turn < 10; turn++) await Promise.resolve();
    }
  }
  assert.equal(requests.length, 1, "expected one relay batch from the extracted ZIP asset");
  const request = requests[0];
  assert.equal(request.url, config.endpoint);
  assert.equal(request.headers.get("authorization"), null);
  assert.equal(request.batch.length, 1);
  const event = request.batch[0];
  assert.equal(event.sdk_version, receipt.version);
  assert.equal(event.sdk_name, "@debugbundle/sdk-browser");
  assert.equal(event.service.name, config.service);
  assert.equal(event.project_token, undefined);
  assert.doesNotMatch(JSON.stringify(request.batch), /ZIP_(?:SOURCE|HOOK|SERVER)_SECRET/);
  if (withHook) assert.equal(event.context.marker, "zip-hook-kept");
  else assert.equal(event.event_type, "frontend_exception");
  timers.clear();
}

await check(false);
await check(true);
console.log(`Extracted WordPress Browser ${receipt.version} asset smoke passed: PHP config, queued delivery, hook drop/privacy and credential-free relay.`);
