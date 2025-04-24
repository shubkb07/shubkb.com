# WordPress Development Projects

This repository contains the WiseSync plugin and PaperSync theme development projects.

## Setup Instructions

After setting up WordPress, follow these steps to set up the development environment:

### Step 1: Delete content inside wp-content
First, delete all existing content inside your WordPress wp-content directory.

### Step 2: Clone the repository
Run the following command inside your empty wp-content folder:
```bash
git clone git@github.com:shubkb07/shubkb.com.git .
```

### Step 3: Install dependencies
On Windows:
```bash
npm install && npm run install
```

On Linux and Mac:
```bash
npm install && npm run install:linux
```

> **Note:** The standard `install` script can be run on Linux too, but the `:linux` version cannot run on Windows.

## Development Commands

### Building
To build blocks and language files:
```bash
npm run build
```

If you installed with the Linux script:
```bash
npm run build:linux
```

### Linting
To run all linting checks:
```bash
npm run lint
```

To automatically fix linting issues:
```bash
npm run lint:fix
```

### Individual Scripts
You can run individual build and lint scripts as needed. See the `scripts` section in `package.json` for the complete list of available commands.

## Projects

### WiseSync Plugin
The WiseSync plugin is located in the `plugins/wisesync` directory. See its [documentation](plugins/wisesync/README.md) for more details.

### PaperSync Theme
The PaperSync theme is located in the `themes/papersync` directory. See its [documentation](themes/papersync/README.md) for more details.

## Support & Contribution

- For support requests, see [SUPPORT.md](SUPPORT.md)
- To contribute to the projects, see [CONTRIBUTING.md](CONTRIBUTING.md)
- For security information, see [SECURITY.md](SECURITY.md)
- Please adhere to our [Code of Conduct](CODE_OF_CONDUCT.md)

## License

This project is licensed under the terms of the [ISC license](LICENSE.md).