# YOURLS Plugin: referer-restrict  [![Listed in Awesome YOURLS!](https://img.shields.io/badge/Awesome-YOURLS-C5A3BE)](https://github.com/YOURLS/awesome-yourls/)

A YOURLS-based plugin to restrict access from specific referrer domains.

[中文文档](https://github.com/twsh0305/referer-restrict/blob/main/README_CN.md)

<img width="2165" height="551" alt="image" src="https://github.com/user-attachments/assets/d05d9c51-bb70-4318-be53-122db886efa4" />

## Installation Steps
YOURLS Installation Guide: [https://wxsnote.cn/6633.html](https://wxsnote.cn/6633.html)

1. Download the plugin from the releases page and extract it to the `/user/plugins/` directory.
2. Place all files of this plugin into the extracted folder.
3. Visit the plugin management page (`/admin/plugins.php`) and activate the plugin.
4. Start using it!

## Translation Support
1. The plugin supports custom language translation. Its `languages` directory contains the `referer-restrict-zh_CN.po` file - please download this file first.
2. Replace `zh_CN` in the downloaded filename with your target region's language code (e.g., `en_US`).
3. Download and use the [Poedit](https://poedit.net/) tool to open the modified `.po` file, then complete the translation for your target language.
4. After finishing the translation, save the file via Poedit - this will automatically generate a corresponding `.mo` file (same filename as the `.po` file, different extension).
5. Place both the translated `.po` file (e.g., `referer-restrict-en_US.po`) and the automatically generated `.mo` file into the plugin's `languages` directory (Note: Not YOURLS' own `languages` directory).
6. After completing the above steps, you can enjoy the plugin's custom language support! Feel free to submit your translation files to the plugin repository to contribute to the multilingual ecosystem.

## License

Free for any use - you can perform any operations on this plugin without restrictions.

Author's Blog: [Mr. Wang's Notes](https://wxsnote.cn)
