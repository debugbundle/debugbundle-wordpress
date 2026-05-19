import { createDebugBundleBrowserSdk } from "@debugbundle/sdk-browser";

declare global {
  interface Window {
    DebugBundleWordPressConfig?: Record<string, unknown>;
    DebugBundleWordPress?: unknown;
  }
}

const config = window.DebugBundleWordPressConfig;

if (config && typeof config === "object") {
  try {
    const sdk = createDebugBundleBrowserSdk();
    sdk.init(config as Parameters<typeof sdk.init>[0]);

    if ((config as Record<string, unknown>).debug === true) {
      window.DebugBundleWordPress = sdk;
    }
  } catch {
    // Intentionally fail closed: the plugin must never break the page.
  }
}
