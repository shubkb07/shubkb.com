import { execSync } from 'child_process';
import fs from 'fs';
import path from 'path';

console.log(process.cwd());

// Process command line arguments
const args = process.argv.slice(2);
const isLinux = args.includes('--linux') || args.includes('--linix');

// Set WP_CLI based on command line args
const WP_CLI = isLinux ? 'wp' : 'php wp-cli.phar';

console.log(`Using WP_CLI command: ${WP_CLI}`);

// Define paths
const PLUGIN_PATH = './plugins/wisesync';
const PLUGIN_DOMAIN = 'wisesync';
const THEME_PATH = './themes/papersync';
const THEME_DOMAIN = 'papersync';

// Function to execute commands and log output
function executeCommand(command) {
  console.log(`\x1b[36mExecuting: ${command}\x1b[0m`);
  try {
    const output = execSync(command, { encoding: 'utf8' });
    console.log(`\x1b[32mSuccess:\x1b[0m ${output}`);
    return true;
  } catch (error) {
    console.error(`\x1b[31mError executing command:\x1b[0m ${error.message}`);
    return false;
  }
}

// Function to ensure directories exist
function ensureDirExists(dirPath) {
  if (!fs.existsSync(dirPath)) {
    console.log(`Creating directory: ${dirPath}`);
    fs.mkdirSync(dirPath, { recursive: true });
  }
}

// Main execution function
function buildTranslations() {
  console.log('\x1b[33m======== Starting WordPress i18n Build Process ========\x1b[0m');
  
  // Ensure language directories exist
  ensureDirExists(`${PLUGIN_PATH}/languages`);
  ensureDirExists(`${THEME_PATH}/languages`);
  
  // Step 1: Generate POT files
  console.log('\n\x1b[33m>> Generating POT files...\x1b[0m');
  executeCommand(`${WP_CLI} i18n make-pot ${PLUGIN_PATH} ${PLUGIN_PATH}/languages/${PLUGIN_DOMAIN}.pot --domain=${PLUGIN_DOMAIN}`);
  executeCommand(`${WP_CLI} i18n make-pot ${THEME_PATH} ${THEME_PATH}/languages/${THEME_DOMAIN}.pot --domain=${THEME_DOMAIN}`);
  
  // Step 2: Update PO files
  console.log('\n\x1b[33m>> Updating PO files...\x1b[0m');
  executeCommand(`${WP_CLI} i18n update-po ${PLUGIN_PATH}/languages/${PLUGIN_DOMAIN}.pot ${PLUGIN_PATH}/languages/`);
  executeCommand(`${WP_CLI} i18n update-po ${THEME_PATH}/languages/${THEME_DOMAIN}.pot ${THEME_PATH}/languages/`);
  
  // Step 3: Generate JSON files for JS translations
  console.log('\n\x1b[33m>> Generating JSON translation files for JavaScript...\x1b[0m');
  executeCommand(`${WP_CLI} i18n make-json ${PLUGIN_PATH}/languages/`);
  executeCommand(`${WP_CLI} i18n make-json ${THEME_PATH}/languages/`);
  
  // Step 4: Generate MO files
  console.log('\n\x1b[33m>> Generating MO files...\x1b[0m');
  executeCommand(`${WP_CLI} i18n make-mo ${PLUGIN_PATH}/languages/`);
  executeCommand(`${WP_CLI} i18n make-mo ${THEME_PATH}/languages/`);
  
  console.log('\n\x1b[33m======== Translation Build Process Complete ========\x1b[0m');
}

// Execute the main function
buildTranslations();