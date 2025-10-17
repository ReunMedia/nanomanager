# Release Workflow

- [ ] Run `bun pm version --no-git-tag-version` in project root to bump version
- [ ] Run `bun moon run root:prepare-release`
- [ ] Build PHAR with `bun moon run php:build`
- [ ] Test PHAR with `bun moon run php:preview`
- [ ] Commit and tag
- [ ] Push with tags
- [ ] [Create a new release](https://github.com/ReunMedia/nanomanager/releases/new)
- [ ] Add `Nanomanager.PHAR` and `nanomanager.umd.cjs` as additional files
