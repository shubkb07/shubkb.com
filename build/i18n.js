import { execSync } from 'child_process';
import fs from 'fs';

console.log(process.cwd());

const locales = [
  { name: "Afrikaans", code: "af", locale: "af" },
  { name: "Albanian", code: "sq", locale: "sq" },
  { name: "Algerian Arabic", code: "arq", locale: "arq" },
  { name: "Akan", code: "ak", locale: "ak" },
  { name: "Amharic", code: "am", locale: "am" },
  { name: "Arabic", code: "ar", locale: "ar" },
  { name: "Armenian", code: "hy", locale: "hy" },
  { name: "Aromanian", code: "rup", locale: "rup_MK" },
  { name: "Arpitan", code: "frp", locale: "frp" },
  { name: "Assamese", code: "as", locale: "as" },
  { name: "Asturian", code: "ast", locale: "ast" },
  { name: "Azerbaijani", code: "az", locale: "az" },
  { name: "Azerbaijani (Turkey)", code: "az-tr", locale: "az_TR" },
  { name: "Balochi Southern", code: "bcc", locale: "bcc" },
  { name: "Bashkir", code: "ba", locale: "ba" },
  { name: "Basque", code: "eu", locale: "eu" },
  { name: "Belarusian", code: "bel", locale: "bel" },
  { name: "Bengali", code: "bn", locale: "bn_BD" },
  { name: "Bengali (India)", code: "bn-in", locale: "bn_IN" },
  { name: "Bhojpuri", code: "bho", locale: "bho" },
  { name: "Bodo", code: "brx", locale: "brx" },
  { name: "Borana-Arsi-Guji Oromo", code: "gax", locale: "gax" },
  { name: "Bosnian", code: "bs", locale: "bs_BA" },
  { name: "Breton", code: "br", locale: "bre" },
  { name: "Bulgarian", code: "bg", locale: "bg_BG" },
  { name: "Burmese", code: "mya", locale: "my_MM" },
  { name: "Catalan", code: "ca", locale: "ca" },
  { name: "Catalan (Balear)", code: "bal", locale: "bal" },
  { name: "Cebuano", code: "ceb", locale: "ceb" },
  { name: "Chinese (China)", code: "zh-cn", locale: "zh_CN" },
  { name: "Chinese (Hong Kong)", code: "zh-hk", locale: "zh_HK" },
  { name: "Chinese (Singapore)", code: "zh-sg", locale: "zh_SG" },
  { name: "Chinese (Taiwan)", code: "zh-tw", locale: "zh_TW" },
  { name: "Cornish", code: "cor", locale: "cor" },
  { name: "Corsican", code: "co", locale: "co" },
  { name: "Croatian", code: "hr", locale: "hr" },
  { name: "Czech", code: "cs", locale: "cs_CZ" },
  { name: "Danish", code: "da", locale: "da_DK" },
  { name: "Dhivehi", code: "dv", locale: "dv" },
  { name: "Dutch", code: "nl", locale: "nl_NL" },
  { name: "Dutch (Belgium)", code: "nl-be", locale: "nl_BE" },
  { name: "Dzongkha", code: "dzo", locale: "dzo" },
  { name: "Emoji", code: "art-xemoji", locale: "art-xemoji" },
  { name: "English", code: "en", locale: "en_US" },
  { name: "English (Australia)", code: "en-au", locale: "en_AU" },
  { name: "English (Canada)", code: "en-ca", locale: "en_CA" },
  { name: "English (New Zealand)", code: "en-nz", locale: "en_NZ" },
  { name: "English (Pirate)", code: "art_xpirate", locale: "art_xpirate" },
  { name: "English (South Africa)", code: "en-sa", locale: "en_SA" },
  { name: "English (UK)", code: "en-gb", locale: "en_GB" },
  { name: "Esperanto", code: "eo", locale: "eo" },
  { name: "Estonian", code: "et", locale: "et" },
  { name: "Ewe", code: "ewe", locale: "ewe" },
  { name: "Faroese", code: "fo", locale: "fo" },
  { name: "Finnish", code: "fi", locale: "fi" },
  { name: "Fon", code: "fon", locale: "fon" },
  { name: "French (Belgium)", code: "fr-be", locale: "fr_BE" },
  { name: "French (Canada)", code: "fr-ca", locale: "fr_CA" },
  { name: "French (France)", code: "fr", locale: "fr_FR" },
  { name: "Frisian", code: "fy", locale: "fy" },
  { name: "Friulian", code: "fur", locale: "fur" },
  { name: "Fulah", code: "fuc", locale: "fuc" },
  { name: "Galician", code: "gl", locale: "gl_ES" },
  { name: "Georgian", code: "ka", locale: "ka_GE" },
  { name: "German", code: "de", locale: "de_DE" },
  { name: "German (Austria)", code: "de-AT", locale: "de_AT" },
  { name: "German (Switzerland)", code: "de-ch", locale: "de_CH" },
  { name: "Greek", code: "el", locale: "el" },
  { name: "Greenlandic", code: "kal", locale: "kal" },
  { name: "Guaraní", code: "gn", locale: "gn" },
  { name: "Gujarati", code: "gu", locale: "gu_IN" },
  { name: "Hawaiian", code: "haw", locale: "haw_US" },
  { name: "Haitian Creole", code: "hat", locale: "hat" },
  { name: "Hausa", code: "hau", locale: "hau" },
  { name: "Hazaragi", code: "haz", locale: "haz" },
  { name: "Hebrew", code: "he", locale: "he_IL" },
  { name: "Hindi", code: "hi", locale: "hi_IN" },
  { name: "Hungarian", code: "hu", locale: "hu_HU" },
  { name: "Icelandic", code: "is", locale: "is_IS" },
  { name: "Ido", code: "ido", locale: "ido" },
  { name: "Igbo", code: "ibo", locale: "ibo" },
  { name: "Indonesian", code: "id", locale: "id_ID" },
  { name: "Irish", code: "ga", locale: "ga" },
  { name: "Italian", code: "it", locale: "it_IT" },
  { name: "Japanese", code: "ja", locale: "ja" },
  { name: "Javanese", code: "jv", locale: "jv_ID" },
  { name: "Kabyle", code: "kab", locale: "kab" },
  { name: "Kannada", code: "kn", locale: "kn" },
  { name: "Karakalpak", code: "kaa", locale: "kaa" },
  { name: "Kazakh", code: "kk", locale: "kk" },
  { name: "Khmer", code: "km", locale: "km" },
  { name: "Kinyarwanda", code: "kin", locale: "kin" },
  { name: "Kirghiz", code: "ky", locale: "ky_KY" },
  { name: "Korean", code: "ko", locale: "ko_KR" },
  { name: "Kurdish (Sorani)", code: "ckb", locale: "ckb" },
  { name: "Kurdish (Kurmanji)", code: "kmr", locale: "kmr" },
  { name: "Kyrgyz", code: "kir", locale: "kir" },
  { name: "Lao", code: "lo", locale: "lo" },
  { name: "Latvian", code: "lv", locale: "lv" },
  { name: "Latin", code: "la", locale: "la" },
  { name: "Ligurian", code: "lij", locale: "lij" },
  { name: "Limburgish", code: "li", locale: "li" },
  { name: "Lingala", code: "lin", locale: "lin" },
  { name: "Lithuanian", code: "lt", locale: "lt_LT" },
  { name: "Lombard", code: "lmo", locale: "lmo" },
  { name: "Lower Sorbian", code: "dsb", locale: "dsb" },
  { name: "Luganda", code: "lug", locale: "lug" },
  { name: "Luxembourgish", code: "lb", locale: "lb_LU" },
  { name: "Macedonian", code: "mk", locale: "mk_MK" },
  { name: "Maithili", code: "mai", locale: "mai" },
  { name: "Malagasy", code: "mg", locale: "mg_MG" },
  { name: "Maltese", code: "mlt", locale: "mlt" },
  { name: "Malay", code: "ms", locale: "ms_MY" },
  { name: "Malayalam", code: "ml", locale: "ml_IN" },
  { name: "Maori", code: "mri", locale: "mri" },
  { name: "Mauritian Creole", code: "mfe", locale: "mfe" },
  { name: "Marathi", code: "mr", locale: "mr" },
  { name: "Mingrelian", code: "xmf", locale: "xmf" },
  { name: "Mongolian", code: "mn", locale: "mn" },
  { name: "Montenegrin", code: "me", locale: "me_ME" },
  { name: "Moroccan Arabic", code: "ary", locale: "ary" },
  { name: "Myanmar (Burmese)", code: "mya", locale: "my_MM" },
  { name: "Nepali", code: "ne", locale: "ne_NP" },
  { name: "Nigerian Pidgin", code: "pcm", locale: "pcm" },
  { name: "N’ko", code: "nqo", locale: "nqo" },
  { name: "Norwegian (Bokmål)", code: "nb", locale: "nb_NO" },
  { name: "Norwegian (Nynorsk)", code: "nn", locale: "nn_NO" },
  { name: "Occitan", code: "oci", locale: "oci" },
  { name: "Oriya", code: "ory", locale: "ory" },
  { name: "Ossetic", code: "os", locale: "os" },
  { name: "Pashto", code: "ps", locale: "ps" },
  { name: "Panjabi (India)", code: "pa", locale: "pa_IN" },
  { name: "Papiamento (Aruba)", code: "pap-AW", locale: "pap_AW" },
  {
    name: "Papiamento (Curaçao and Bonaire)",
    code: "pap-CW",
    locale: "pap_CW",
  },
  { name: "Persian", code: "fa", locale: "fa_IR" },
  { name: "Persian (Afghanistan)", code: "fa-af", locale: "fa_AF" },
  { name: "Polish", code: "pl", locale: "pl_PL" },
  { name: "Portuguese (Angola)", code: "pt-AO", locale: "pt_AO" },
  { name: "Portuguese (Brazil)", code: "pt-br", locale: "pt_BR" },
  { name: "Portuguese (Portugal)", code: "pt", locale: "pt_PT" },
  { name: "Punjabi", code: "pa", locale: "pa_IN" },
  { name: "Rohingya", code: "rhg", locale: "rhg" },
  { name: "Romanian", code: "ro", locale: "ro_RO" },
  { name: "Romansh", code: "roh", locale: "roh" },
  { name: "Russian", code: "ru", locale: "ru_RU" },
  { name: "Russian (Ukraine)", code: "ru-ua", locale: "ru_UA" },
  { name: "Rusyn", code: "rue", locale: "rue" },
  { name: "Sakha", code: "sah", locale: "sah" },
  { name: "Sanskrit", code: "sa-in", locale: "sa_IN" },
  { name: "Saraiki", code: "skr", locale: "skr" },
  { name: "Sardinian", code: "srd", locale: "srd" },
  { name: "Scottish Gaelic", code: "gd", locale: "gd" },
  { name: "Serbian", code: "sr", locale: "sr_RS" },
  { name: "Shona", code: "sna", locale: "sna" },
  { name: "Shqip (Kosovo)", code: "sq", locale: "sq_XK" },
  { name: "Sicilian", code: "scn", locale: "scn" },
  { name: "Sindhi", code: "sd", locale: "sd_PK" },
  { name: "Sinhala", code: "si", locale: "si_LK" },
  { name: "Silesian", code: "szl", locale: "szl" },
  { name: "Slovak", code: "sk", locale: "sk_SK" },
  { name: "Slovenian", code: "sl", locale: "sl_SI" },
  { name: "Somali", code: "so", locale: "so_SO" },
  { name: "South Azerbaijani", code: "azb", locale: "azb" },
  { name: "Spanish (Argentina)", code: "es-ar", locale: "es_AR" },
  { name: "Spanish (Chile)", code: "es-cl", locale: "es_CL" },
  { name: "Spanish (Costa Rica)", code: "es-CR", locale: "es_CR" },
  { name: "Spanish (Colombia)", code: "es-co", locale: "es_CO" },
  { name: "Spanish (Dominican Republic)", code: "es-DO", locale: "es_DO" },
  { name: "Spanish (Ecuador)", code: "es-EC", locale: "es_EC" },
  { name: "Spanish (Guatemala)", code: "es-gt", locale: "es_GT" },
  { name: "Spanish (Honduras)", code: "es-HN", locale: "es_HN" },
  { name: "Spanish (Mexico)", code: "es-mx", locale: "es_MX" },
  { name: "Spanish (Peru)", code: "es-pe", locale: "es_PE" },
  { name: "Spanish (Puerto Rico)", code: "es-pr", locale: "es_PR" },
  { name: "Spanish (Spain)", code: "es", locale: "es_ES" },
  { name: "Spanish (Uruguay)", code: "es-UY", locale: "es_UY" },
  { name: "Spanish (Venezuela)", code: "es-ve", locale: "es_VE" },
  { name: "Sundanese", code: "su", locale: "su_ID" },
  { name: "Swati", code: "ssw", locale: "ssw" },
  { name: "Swahili", code: "sw", locale: "sw" },
  { name: "Swedish", code: "sv", locale: "sv_SE" },
  { name: "Swiss German", code: "gsw", locale: "gsw" },
  { name: "Syriac", code: "syr", locale: "syr" },
  { name: "Tagalog", code: "tl", locale: "tl" },
  { name: "Tahitian", code: "tah", locale: "tah" },
  { name: "Tajik", code: "tg", locale: "tg" },
  { name: "Tamazight (Central Atlas)", code: "tzm", locale: "tzm" },
  { name: "Tamazight", code: "zgh", locale: "zgh" },
  { name: "Tamil", code: "ta", locale: "ta_IN" },
  { name: "Tamil (Sri Lanka)", code: "ta-lk", locale: "ta_LK" },
  { name: "Tatar", code: "tt", locale: "tt_RU" },
  { name: "Telugu", code: "te", locale: "te" },
  { name: "Thai", code: "th", locale: "th" },
  { name: "Tibetan", code: "bo", locale: "bo" },
  { name: "Tigrinya", code: "tir", locale: "tir" },
  { name: "Turkish", code: "tr", locale: "tr_TR" },
  { name: "Turkmen", code: "tuk", locale: "tuk" },
  { name: "Tweants", code: "twd", locale: "twd" },
  { name: "Uighur", code: "ug", locale: "ug_CN" },
  { name: "Ukrainian", code: "uk", locale: "uk" },
  { name: "Upper Sorbian", code: "hsb", locale: "hsb" },
  { name: "Urdu", code: "ur", locale: "ur" },
  { name: "Uzbek", code: "uz", locale: "uz_UZ" },
  { name: "Venetian", code: "vec", locale: "vec" },
  { name: "Vietnamese", code: "vi", locale: "vi" },
  { name: "Walloon", code: "wa", locale: "wa" },
  { name: "Welsh", code: "cy", locale: "cy" },
  { name: "Wolof", code: "wol", locale: "wol" },
  { name: "Xhosa", code: "xho", locale: "xho" },
  { name: "Yoruba", code: "yor", locale: "yor" },
  { name: "Zulu", code: "zul", locale: "zul" },
];

// Define paths
const PLUGIN_PATH = './plugins/wisesync';
const PLUGIN_DOMAIN = 'wisesync';
const THEME_PATH = './themes/papersync';
const THEME_DOMAIN = 'papersync';
const WP_CLI = 'wp';

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