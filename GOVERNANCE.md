The Wikimate project strives for a transparent and welcoming maintenance process.
Below are some guidelines for current and prospective maintainers.
Anyone is welcome to propose changes to this document via issues or pull requests.

Regular contributors are to be given collaborator status
(which includes commit access, the ability to merge PRs, make releases, etc.).
Note that at the moment this can only be done by the repository owner, [@hamstar](https://github.com/hamstar).
Contributors that have had a few PRs merged are encouraged to ask for collaborator access,
if they would like to help maintaining the repository.

## Guidelines for commits and pull requests

1. **Every change should be made via pull requests**,
   rather than as direct commits to the `master` branch
   (except for very minor ones such as spelling fixes).
2. **Maintainers should not merge their own pull requests**.
   This allows every change to be validated by at least another maintainer.
   That said, if there's no input by other maintainers in over a week,
   the maintainer who authored the pull request can merge their own PR.
3. Pull requests should comprise a single feature or bugfix.
   **Unrelated changes should be sent as separate PRs.**
   The exception are minor code cleanup changes,
   which can be included (as a separate commit) in the PR that prompted them.
4. **Commits should be atomic**
   (as small as possible while still representing a self-consistent set of changes)
   and have descriptive commit messages.
5. **Every PR should include a CHANGELOG.md entry**.
   This makes it much easier to prepare releases,
   and allows the change author to properly summarize it.

## Process for releasing a new version of Wikimate

A PR should be created with all relevant changes to update the repository for the upcoming release.
(See [#81](https://github.com/hamstar/Wikimate/pull/81) for an example.)
It should apply the following actions:

1. Change the "Unreleased version" heading in the CHANGELOG.md file
   to the appropriate version name (e.g. "Version 1.2.3")
   and add a new "Unreleased version" section heading above it;
2. Edit the README and replace all references
   to the previous version number and release date
   with the corresponding data for the new version;
3. Update all version references in Wikimate.php
   to the new version.

Once this PR is merged, a new release should be created
in https://github.com/hamstar/Wikimate/releases/new
(collaborator status is required for this step).

The version tag and the title of the release notes should be in the format v1.2.3
(following [SemVer](http://semver.org/) conventions
to determine which part of the version number to increase).
The body of the release notes should be a summary of the contents
of the relevant section in CHANGELOG.md.
