# Gitignore Generator Console Application

![GitHub tag (latest by date)](https://img.shields.io/github/v/tag/shahmal1yev/ignore?label=latest&style=flat)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)
![GitHub last commit](https://img.shields.io/github/last-commit/shahmal1yev/ignore)
![GitHub issues](https://img.shields.io/github/issues/shahmal1yev/ignore)
![GitHub stars](https://img.shields.io/github/stars/shahmal1yev/ignore)
![GitHub forks](https://img.shields.io/github/forks/shahmal1yev/ignore)
![GitHub contributors](https://img.shields.io/github/contributors/shahmal1yev/ignore)

This console application provides commands to create `.gitignore` files by retrieving content from Gitignore.io based on selected patterns.

## Features

- List available gitignore patterns.
- Create a `.gitignore` file based on selected patterns.
- Save the generated `.gitignore` file to a specified path.

## Installation

1. Clone the repository:

    ```bash
    git clone <repository_url>
    ```

2. Navigate to the project directory:

    ```bash
    cd <repository_name>
    ```

3. Install dependencies using Composer:

    ```bash
    composer install
    ```
   
## Integration with Git

- Clone the repository.
- Run ```composer install``` in the local repo directory.
- Create a symlink: ```sudo ln -s <local-repo-absolute-path/bin/console> /usr/local/bin/git-ignore```
- Verify that it works: ```git ignore```

## Usage

### List Available Patterns

To list available gitignore patterns, run:

```bash
php bin/console patterns
```

To create a .gitignore file based on selected patterns, run:

```bash
php bin/console create --path ./
```

To create a .gitignore file based on passed patterns, run:

```bash
php bin/console create --patterns composer --patterns phpstorm+all --path ./
```

## Commands 

- **ignore-patterns:** Fetches and displays the available gitignore patterns from Gitignore.io.
- **create-gitignore:** Creates a .gitignore file based on the selected patterns and optionally saves it to a specified path.

## Aliases

- **ignore-patterns:** patterns
- **create-gitignore:** create

## Options

- **create-gitignore:**
  - **--path|-pt:** Path to save the generated .gitignore file. If not specified, the file will not be saved.
  - **--patterns|-p:** Patterns to retrieve from gitignore.io. This option can be specified multiple times.
- **quiet|-q:** Run the command in quiet mode.