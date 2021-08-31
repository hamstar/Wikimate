# Contribution guidelines

The Wikimate project welcomes contributions of [all kinds](https://allcontributors.org/docs/en/emoji-key),
no matter how small!
Feel free to open [an issue](https://github.com/hamstar/Wikimate/issues/new)
to discuss your suggestion or request,
or submit your changes directly as a
[pull request](https://docs.github.com/en/github/collaborating-with-pull-requests/proposing-changes-to-your-work-with-pull-requests/creating-a-pull-request-from-a-fork)
if you prefer.

Below are some guidelines that might help you prepare your contribution.

1. **Limit pull requests limited to a single feature or bugfix.**
   Unrelated changes should be sent as separate PRs.
   The exception are minor cleanup changes,
   which can be included (as a separate commit) in the PR that prompted them.
2. **Use atomic commits**.
   Try to create commits that are as small as possible
   while still representing a self-contained set of changes,
   and use descriptive commit messages.
   We recommend [these principles](https://chris.beams.io/posts/git-commit/#seven-rules)
   for writing great commit messages (but they're not enforced, so don't sweat it 🙂).
3. **Include a `CHANGELOG.md` entry in every pull request**.
   This makes it much easier to prepare releases,
   and allows the author of each change to properly summarize it.
4. **Update the documentation in `USAGE.md` if necessary**.
   When writing prose, break lines [semantically](https://rhodesmill.org/brandon/2012/one-sentence-per-line/).
   We don't have a strict maximum line length
   (especially since things like long URLs can easily surpass them),
   but you should start thinking about breaking lines
   once they're approaching 100 characters in length.
