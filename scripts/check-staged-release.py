#!/usr/bin/env python3
"""Install immutable local SDK archives and smoke the resulting WordPress ZIP.

This is pre-publication qualification. It does not certify a registry lock or
produce a publishable release; the ZIP contains an explicit staged marker.
"""
import argparse
import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import tarfile
import zipfile


def run(*args, cwd=None, env=None):
    subprocess.run(args, cwd=cwd, env=env, check=True)


def digest(path, algorithm="sha256"):
    return hashlib.new(algorithm, path.read_bytes()).hexdigest()


def write_json(path, value):
    path.write_text(json.dumps(value, indent=2) + "\n")


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--php-sdk", required=True, type=Path)
    parser.add_argument("--browser-artifact", required=True, type=Path)
    parser.add_argument("--version", default="2.1.1")
    parser.add_argument("--core-dependencies", choices=("candidate", "published"), default="candidate",
                        help="Use packed local core candidates, or the Browser artifact's exact published dependency pins")
    args = parser.parse_args()
    repo = Path(__file__).resolve().parents[1]
    php = args.php_sdk.resolve()
    browser = args.browser_artifact.resolve()
    version = args.version
    if not re.fullmatch(r"2\.[0-9]+\.[0-9]+", version):
        parser.error("Provide a WordPress 2.x candidate version")
    if not browser.is_file() or not (php / "src/DebugBundleSdk.php").is_file():
        parser.error("Provide the PHP checkout and the built browser 3.x .tgz")
    php_version = re.search(r"SDK_VERSION = '([^']+)'", (php / "src/DebugBundleSdk.php").read_text())[1]
    if not php_version.startswith("2."):
        parser.error("The staged plugin requires PHP SDK 2.x")
    stage = repo / ".tmp/staged-release"
    if stage.exists():
        shutil.rmtree(stage)
    consumer = stage / "wordpress"
    artifacts = stage / "artifacts"
    artifacts.mkdir(parents=True)
    consumer.mkdir()
    files = subprocess.check_output(["git", "ls-files", "--cached", "--others", "--exclude-standard", "-z"], cwd=repo)
    for name in set(files.decode().split("\0")) - {""}:
        source = repo / name
        if source.is_file():
            target = consumer / name
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(source, target)
    php_package = json.loads((php / "composer.json").read_text())
    php_package["version"] = php_version
    php_zip = artifacts / f"debugbundle-sdk-php-{php_version}.zip"
    with zipfile.ZipFile(php_zip, "w", zipfile.ZIP_DEFLATED) as archive:
        archive.writestr("composer.json", json.dumps(php_package, indent=2) + "\n")
        for source in sorted((php / "src").rglob("*")):
            if source.is_file():
                archive.write(source, source.relative_to(php))
        for name in ("LICENSE", "README.md", "CHANGELOG.md", "MIGRATION-2.0.md", "MIGRATION-2.1.md"):
            if (php / name).is_file():
                archive.write(php / name, name)
    with tarfile.open(browser) as archive:
        browser_package = json.load(archive.extractfile("package/package.json"))
    if browser_package["name"] != "@debugbundle/sdk-browser" or not browser_package["version"].startswith("3."):
        parser.error("Provide the built hardened browser 3.x tarball")
    shutil.copy2(browser, artifacts / browser.name)
    receipt = {"kind": "staged-prepublication-only", "wordpress_version": version, "php_version": php_version,
               "php_sha256": digest(php_zip), "browser_artifact": browser.name,
               "browser_sha256": digest(browser)}
    write_json(consumer / ".debugbundle-staged-candidate.json", receipt)
    composer = json.loads((consumer / "composer.json").read_text())
    composer["require"]["debugbundle/sdk-php"] = "^" + php_version
    package = {key: php_package[key] for key in ("name", "version", "type", "require", "autoload", "license") if key in php_package}
    package["dist"] = {"type": "zip", "url": f"file:///stage/artifacts/{php_zip.name}", "shasum": digest(php_zip, "sha1")}
    composer["repositories"] = [{"type": "package", "canonical": True, "package": package}]
    write_json(consumer / "composer.json", composer)
    package_json = json.loads((consumer / "package.json").read_text())
    package_json["version"] = version
    package_json["dependencies"]["@debugbundle/sdk-browser"] = f"file:../artifacts/{browser.name}"
    # Consume exact packed core dependencies, never an unpublished source link.
    overrides = {}
    receipt["core_dependencies"] = args.core_dependencies
    for name in ("redaction", "shared-types"):
        root = repo.parents[1]
        dependency_version = json.loads((root / f"packages/{name}/package.json").read_text())["version"]
        declared = browser_package["dependencies"][f"@debugbundle/{name}"]
        if args.core_dependencies == "published":
            if not re.fullmatch(r"[0-9]+\.[0-9]+\.[0-9]+", declared):
                raise RuntimeError(f"Browser must pin an exact published {name} version, got {declared}")
            receipt[f"{name}_version"] = declared
            continue
        if declared != dependency_version:
            raise RuntimeError(f"Browser declares {name} {declared}, expected candidate {dependency_version}")
        archive = root / f".tmp/apache-packages/debugbundle-{name}-{dependency_version}.tgz"
        shutil.copy2(archive, artifacts / archive.name)
        receipt[f"{name}_sha256"] = digest(archive)
        overrides[f"@debugbundle/{name}"] = f"file:../artifacts/{archive.name}"
    package_json["pnpm"] = {"overrides": overrides}
    write_json(consumer / "package.json", package_json)
    write_json(consumer / ".debugbundle-staged-candidate.json", receipt)
    for name in ("debugbundle.php", "readme.txt"):
        value = (consumer / name).read_text()
        if name == "debugbundle.php":
            value = re.sub(r"(?m)^( \* Version:\s+)[^\n]+", lambda match: match[1] + version, value)
            value = re.sub(r"(DEBUGBUNDLE_WORDPRESS_VERSION', ')[^']+", lambda match: match[1] + version, value)
            value = re.sub(r"(DEBUGBUNDLE_WORDPRESS_BROWSER_SDK_VERSION', ')[^']+", lambda match: match[1] + browser_package['version'], value)
        else:
            value = re.sub(r"(?m)^Stable tag: .+$", "Stable tag: " + version, value)
        (consumer / name).write_text(value)
    changelog = consumer / "CHANGELOG.md"
    changelog.write_text(changelog.read_text().replace("## [Unreleased]", f"## [{version}] - Staged candidate"))
    mount = f"{stage}:/stage"
    run("docker", "run", "--rm", "-v", mount, "-w", "/stage/wordpress", "composer:2",
        "composer", "update", "debugbundle/sdk-php", "--with-all-dependencies", "--no-interaction", "--no-progress")
    run("docker", "run", "--rm", "-v", mount, "-w", "/stage/wordpress", "node:24-alpine", "sh", "-lc",
        "corepack enable && corepack pnpm install --frozen-lockfile=false && corepack pnpm build")
    run("docker", "run", "--rm", "-v", mount, "-w", "/stage/wordpress", "-e", "DEBUGBUNDLE_STAGED_ARTIFACT_CHECK=1",
        "composer:2", "./scripts/assemble-release.sh", version)
    artifact = consumer / f".dist/debugbundle-wordpress-{version}.zip"
    with zipfile.ZipFile(artifact) as archive:
        assert json.loads(archive.read("debugbundle/STAGED-CANDIDATE.json")) == receipt
        assert php_version in archive.read("debugbundle/vendor/debugbundle/sdk-php/src/DebugBundleSdk.php").decode()
    receipt["wordpress_sha256"] = digest(artifact)
    env = {**os.environ, "DEBUGBUNDLE_USE_ASSEMBLED_ARTIFACT": "1", "VERSION": version}
    env.pop("DEBUGBUNDLE_PHP_SDK_CHECKOUT", None)
    run("./scripts/smoke-wordpress.sh", cwd=consumer, env=env)
    receipt["smoke_passed"] = True
    write_json(stage / "qualification.json", receipt)
    print(f"Staged archive qualification passed: {stage / 'qualification.json'}")


if __name__ == "__main__":
    main()
