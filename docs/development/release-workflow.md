# Release Workflow

- [ ] Update CHANGELOG
- [ ] Run `bun pm version --no-git-tag-version` in project root to bump version
- [ ] Run `bun moon run root:prepare-release`
  - This creates and tags a new commit so make sure there are no undesired
    uncommitted changes
- [ ] Build PHAR with `bun moon run php:build`
- [ ] Test PHAR with `bun moon run php:preview`
- [ ] Push with tags
  - `git push --follow-tags`
- [ ] [Create a new release](https://github.com/ReunMedia/nanomanager/releases/new)
- [ ] Add `Nanomanager.PHAR` and `nanomanager.js` as additional files
  - [ ] Rename `nanomanager.js` to `nanomanager-0.0.0.js`
