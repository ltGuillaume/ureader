# uReader
by ltGuillaume: [Codeberg](https://codeberg.org/ltGuillaume) | [GitHub](https://github.com/ltGuillaume) | [Buy me a beer](https://buymeacoff.ee/ltGuillaume) 🍺

Minimal code to present a preformatted plain text document for comfortable reading on mobile and desktop. Some Markdown syntax is supported.

![Screenshot](SCREENSHOT.gif)

## Overview
- Pagination
- Font scaling
- Support for subfolders/chapters
- Keyboard, touch and mouse wheel navigation
- 3 themes: reading mode (blue background), dark and light
- NoScript support (without pagination, font scaling and theme switching)
- Partial Markdown support `#, ##, __, **, [](), ![]()` and HTTP(S) links
- Optional protection with a watchword, passed on directly as URL parameter or entered via a prompt

## Getting started
1. Copy the files to a server with PHP7+
1. (Optional) Copy `config.php.template` to `config.php` and set the variables to your liking
	- For Apache, use `.htaccess` to prevent access to the contents directly
	- For nginx, add something like this to do the same:
	```
    location / {
      rewrite ^([^.\?]*[^/])$ $1/ permanent;  # Add trailing slash for relative links
      rewrite ^ /index.php;                   # Prevent access to anything except for index.php
    }
	```
1. Put the preformatted text in `contents.txt`, or Markdown formatted text in `contents.md`
1. For offering multiple books/chapters you can put `contents.txt`/`contents.md` into subfolders (and optionally add a `config.php` for per-book settings)
1. To share links that include the watchword, append `#ww=your%20watchword` to the URL (needs JavaScript to be enabled). The watchword will not be included in the server logs.

## Credits
* The [Fanwood Text](https://www.theleagueofmoveabletype.com/fanwood) font by Barry Schwartz