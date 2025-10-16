import { $, semver, write } from "bun";
import { resolve } from "node:path";
import frontendPackageJson from "../packages/frontend/package.json";
import phpComposerJson from "../packages/php/composer.json";

/**
 * Project root directory
 */
const rootDir = resolve(import.meta.dir, "..");

/**
 * Makes sure the version argument is newer compared to latest git tag or
 * package metadata
 */
async function isValidVersionArgument(version: string): Promise<boolean> {
  // Get last tag from Git and make sure the version we're releasing is newer
  let lastTag = await $`git describe --tags --abbrev=0 --always`.text();

  // Handle the case of no git tags
  if (lastTag.indexOf(".") === -1) {
    lastTag = "0.0.0";
  }

  // Sort all versions for comparison
  const versions = [
    lastTag,
    frontendPackageJson.version,
    phpComposerJson.version,
    version,
  ].sort(semver.order);
  const lastVersion = versions.at(-1) ?? "0.0.0";

  // Make sure the version we're about to release is newer than any other
  // version.
  if (semver.order(version, lastVersion) === -1) {
    console.error(
      `Version argument must be newer than the last release version (${lastVersion})`,
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
  phpComposerJson.version = version;

  await Promise.all([
    write(
      `${rootDir}/packages/frontend/package.json`,
      JSON.stringify(frontendPackageJson, null, 2) + "\n",
    ),
    await write(
      `${rootDir}/packages/php/composer.json`,
      JSON.stringify(phpComposerJson, null, 4) + "\n",
    ),
    await write(phpFilePath, nanomanagerPhpContent),
  ]);

  await $`composer update`.cwd("./packages/php");
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

  if ((await isValidVersionArgument(version)) === false) {
    return 1;
  }

  console.log(`Preparing to release Nanomanager ${version}`);

  await synchronizePackageVersions(version);

  await buildFrontend();

  return 0;
}

const version = process.argv.slice(2)[0] ?? "";

process.exitCode = await prepareRelease(version);

// export default "";
