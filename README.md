# wpcs-modified
A modified set of WordPress Coding Standards for personal use.

Coding standards are a vital part of any programming project. I do a lot of work
in the WordPress ecosystem, and the base set of standards are a great starting
point for most projects, but I don't love every rule that the community has
chosen to adopt (or not to update over time).

This repo is a modified set of those standards, tailored to my specific needs.
You may like them, too.

# Installation
You can require this modified ruleset as part of your development flow using
Composer.

`composer require --dev jmichaelward/wpcs-modified`

Then, assuming PHPCS is already installed and executable, run

`phpcs --config-set installed_paths <path/to/your/project>`
