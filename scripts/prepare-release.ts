import { $, semver, write } from "bun";
import { resolve } from "node:path";
import rootPackageJson from "../package.json";
import frontendPackageJson from "../packages/frontend/package.json";

/**
 * Project root directory
 */
const rootDir = resolve(import.meta.dir, "..");

/**
 * Makes sure the next version is newer compared to latest git tag or package
 * metadata
 */
async function isValidNextVersion(version: string): Promise<boolean> {
  // Get last tag from Git and make sure the version we're releasing is newer
  let lastTag = await $`git describe --tags --abbrev=0 --always`.text();

  // Handle the case of no git tags
  if (lastTag.indexOf(".") === -1) {
    lastTag = "0.0.0";
  }

  // Make sure the version we're about to release is newer than any other
  // version.
  if (semver.order(version, lastTag) === -1) {
    console.error(
      `Version in package.json must be newer than the last tagged release version (${lastTag}). Run 'bun pm version' in project root to bump the version.`,
    );
    return false;
  }
  return true;
}

async function synchronizePackageVersions(version: string) {
  console.log("Synchronizing package version numbers");

  // Replaace version constant in PHP file
  const phpFilePath = `${rootDir}/packages/php/src/Nanomanager/Nanomanager.php`;
  let nanomanagerPhpContent = await Bun.file(phpFilePath).text();
  nanomanagerPhpContent = nanomanagerPhpContent.replace(
    /(public const VERSION = ').*(';)/,
    `$1${version}$2`,
  );

  frontendPackageJson.version = version;

  await Promise.all([
    write(
      `${rootDir}/packages/frontend/package.json`,
      JSON.stringify(frontendPackageJson, null, 2) + "\n",
    ),
    await write(phpFilePath, nanomanagerPhpContent),
  ]);
}

async function buildFrontend() {
  console.log("Building frontend");

  await $`bun moon run frontend:build`;

  console.log("Copying updated frontend to dist");
  await $`cp -a packages/frontend/dist/. dist/`;
}

async function prepareRelease(version: string): Promise<number> {
  // Make sure we're releasing a valid semver version
  if (version === "" || semver.satisfies(version, "*") === false) {
    console.error(`Invalid version argument "${version}"`);
    return 1;
  }

  // Change working directory to project root
  await $.cwd(rootDir);

  if ((await isValidNextVersion(version)) === false) {
    return 1;
  }

  console.log(`Preparing to release Nanomanager ${version}`);

  await synchronizePackageVersions(version);

  await buildFrontend();

  return 0;
}

const version = rootPackageJson.version;

process.exitCode = await prepareRelease(version);
