import { mkdir } from "node:fs/promises";
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
