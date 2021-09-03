# Governance

This page contains guidelines for current and prospective maintainers.

## Process transparency and contributor onboarding

The Wikimate project strives for a transparent and welcoming maintenance process.
Discussions are expected to happen in public channels, via issues or pull requests,
including proposals to change this document or the [contribution guidelines](CONTRIBUTING.md).

Regular contributors are to be given collaborator status
(which includes commit access, the ability to merge PRs, make releases, etc.).
Note that at the moment this can only be done by the repository owner,
[@hamstar](https://github.com/hamstar).
Contributors that have had a few PRs merged are encouraged to ask for collaborator access,
if they would like to help maintaining the repository.

## Guidelines for pull requests

1. **Every change should be made via pull requests**.
   Do not make commits directly to the `master` branch
   (except for very minor ones such as spelling fixes).
2. **Maintainers should not merge their own pull requests**.
   This allows every change to be validated by at least another maintainer.
   That said, if there's no input by other maintainers in over a week,
   the maintainer who authored the pull request can merge their own PR.
3. **Pull requests should be rebased before merging**.
   The merge should be done with the "Create a merge commit" option.
   This allows preserving individual atomic commits while keeping them grouped per PR,
   and avoiding crossing branches in the git history, which becomes a
   [semi-linear graph](https://devblogs.microsoft.com/devops/pull-requests-with-rebase/#semi-linear-merge).

Note: when creating or handling pull requests,
make sure their contents follow the [contribution guidelines](CONTRIBUTING.md).

## Process for releasing a new version of Wikimate

Create a PR with all relevant changes to update the repository for the upcoming release.
(See [#126](https://github.com/hamstar/Wikimate/pull/126) for an example.)
It should apply the following actions:

1. Change the "Upcoming version" heading in the `CHANGELOG.md` file
   to the appropriate version name and date (e.g. "Version 1.2.3 - 2020-12-31")
   and add a new "Upcoming version" section heading above it,
   with the contents "No changes yet.";
2. Edit `README.md` and replace all references
   to the previous version number and release date
   with the corresponding data for the new version;
3. Update all version references in `Wikimate.php`
   to the new version.

Once this PR is merged, create a new release
in <https://github.com/hamstar/Wikimate/releases/new>
(collaborator status is required for this step).

The version tag and the title of the release notes should be in the format `v1.2.3`
(following [SemVer](http://semver.org/) conventions
to determine which part of the version number to increase).
The body of the release notes should be a summary of the contents
of the relevant section in `CHANGELOG.md`.

Finally, update the Wikimate entry on [Packagist](https://packagist.org/packages/hamstar/wikimate)
(via the "Update Now" link in the right sidebar)
and the corresponding row in MediaWiki.org's
[PHP libraries table](https://www.mediawiki.org/wiki/API:Client_code/All#PHP).
If applicable, also update Wikipedia's
[PHP bot frameworks table](https://en.wikipedia.org/wiki/Wikipedia:PHP_bot_framework_table).
