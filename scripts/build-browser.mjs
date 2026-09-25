import { mkdir, readFile, writeFile } from "node:fs/promises";
import { createHash } from "node:crypto";
import { fileURLToPath } from "node:url";
import { dirname, resolve } from "node:path";
import { build } from "esbuild";

const entry = resolve("assets/src/browser.ts");
const output = resolve("assets/dist/debugbundle-browser.js");

await mkdir(dirname(output), { recursive: true });

await build({
  entryPoints: [entry],
  outfile: output,
  bundle: true,
  format: "iife",
  target: ["es2019"],
  minify: false,
  sourcemap: false,
  legalComments: "none",
});

// The receipt ties the bundled asset to the installed package, including staged
// tarball consumers whose pnpm directory name does not contain a registry version.
let packageDirectory = dirname(fileURLToPath(import.meta.resolve("@debugbundle/sdk-browser")));
let browserPackage;
for (;;) {
  try {
    const candidate = JSON.parse(await readFile(resolve(packageDirectory, "package.json"), "utf8"));
    if (candidate.name === "@debugbundle/sdk-browser") { browserPackage = candidate; break; }
  } catch { /* Walk from the exported entry to its package root. */ }
  const parent = dirname(packageDirectory);
  if (parent === packageDirectory) throw new Error("browser_package_metadata_missing");
  packageDirectory = parent;
}
await writeFile(resolve(dirname(output), "sdk-build.json"), JSON.stringify({
  package: browserPackage.name, version: browserPackage.version,
  asset_sha256: createHash("sha256").update(await readFile(output)).digest("hex")
}, null, 2) + "\n");
