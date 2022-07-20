<?php

$watchword = '';	// Optional protection with a watchword
$markdown  = true;

error_reporting(0);	// E_ALL

$_wrongWw  = 'Wrong watchword!';
$_enterWw  = 'Enter the watchword:';
$_submit   = 'Submit';
$_rTime    = 'Reading time:';
$_rMinutes = 'min';
$_words    = 'words';
$_selMode  = 'Selection mode';
$_setTheme = 'Change theme';
$_decrSize = 'Decrease font size';
$_incrSize = 'Increase font size';
$_gotoPage = 'Go to page';

$uri = $_SERVER["REQUEST_URI"];
$book = basename($uri) ?: ".";
$font = 'fanwood_text.woff';
$fontData = base64_encode(file_get_contents($font));
$contents = file_get_contents('contents.txt');
$words = str_word_count($contents);
$rTime = round($words / 250);
$title = strtok($contents, "\n");

if (isset($watchword)) {
	if (!$_GET["ww"] && !$_POST["ww"])
		$prompt = $_enterWw;
	else if ($_GET['ww'] != $watchword && $_POST['ww'] != $watchword)
		$prompt = "$_wrongWw<br>$_enterWw";

	if ($prompt)
		$contents = <<<WW
			<form action="//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}" method="post">
				<br>{$prompt}<br>
				<input type="password" name="ww" autofocus><br>
				<input type="submit" value="{$_submit}">
			</form>
			<script>
				document.documentElement.className = 'ww';	// Disable white-space and JavaScript
			</script>
WW;
}

if (!isset($prompt)) {
	if ($markdown) {
		function getImage($img) {
			$book = $GLOBALS['book'];
				return '<figure><img src="data:image;base64,'
					. base64_encode(@file_get_contents("$book/$img[2]"))
					."\"><figcaption>$img[1]</figcaption></figure>";
		};

		$contents = preg_replace('/_(.+?)_/m', "<i>$1</i>", $contents);
		$contents = preg_replace('/\*(.+?)\*/m', "<b>$1</b>", $contents);
		$contents = preg_replace('/^##\s*(.+?)$\n/m', "<h2>$1</h2>", $contents);
		$contents = preg_replace('/^#\s*(.+?)$\n/m',  "<h1>$1</h1>", $contents);
		$contents = preg_replace('/(https?:\/\/.+?)(\s)/', "<a href=\"$1\">$1</a>$2", $contents);
		$contents = preg_replace_callback('/!\[(.*?)\]\((.+?)\)/', 'getImage', $contents);
//	$contents = preg_replace('/!\[(.*?)\]\((.+?)\)/', "<img src=\"$uri/$2\" alt=\"$1\" title=\"$1\"/>", $contents);
	}
}

echo <<<END
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
		<noscript>
			<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">
		</noscript>
		<script>
			document.documentElement.className = 'js';
		</script>
		<style>
			@font-face {
				font-family: Fanwood;
				src: url(data:application/x-font-woff;base64,{$fontData});
			}
			:root {
				--lightbg: #cde;
				--lighttxt: #000;
				--darkbg: #141a21;
				--darktxt: #b0b0b0;
				--whitebg: #fff;
			}
			* {
				margin: 0;
				padding: 0;
				font-family: Fanwood, Garamond, "Times New Roman", Times, serif;
				box-sizing: border-box;
			}
			html, body {
				width: 100%;
				height: 100%;
			}
				html.js #controls, html.js #pagenum {
					display: block;
				}
			body, button {
				background: var(--lightbg);
				color: var(--lighttxt);
			}
				body.theme1, body.theme1 button {
					background: var(--darkbg);
					color: var(--darktxt);
				}
				body.theme2, body.theme2 button {
					background: var(--whitebg);
					color: var(--lighttxt);
				}
				@media (prefers-color-scheme: dark) {
					body, button {
						background: var(--darkbg);
						color: var(--darktxt);
					}
						body.theme1, body.theme1 button {
							background: var(--lightbg);
							color: var(--lighttxt);
						}
				}
			button {
				width: 3rem;
				height: 3rem;
				background: 0;
				border: 0;
				outline: 0;
				cursor: pointer;
				user-select: none;
			}
				button::-moz-focus-inner {
					border: 0;
				}
			form {
				text-align: center;
			}
			input {
				margin: .3rem;
				padding: .3rem;
				font-family: sans-serif;
			}
			#controls {
				display: none;
				position: fixed;
				top: 0;
				right: 0;
				opacity: .5;
			}
				#controls span {
					cursor: pointer;
					user-select: none;
				}
			#book {
				height: 100%;
				margin: 0 auto;
				padding: 3rem 20px 2rem 20px;
				font-size: 1.3rem;
				line-height: 1.4;
			}
				html.js #book {
					column-gap: 40px;
					column-width: 100vw;
				}
				@media only screen and (min-width: 1024px) {
					#book {
						max-width: calc(42rem + 40vw);
						padding: 3rem 20vw 2rem 20vw;
					}
						html.js #book {
							column-gap: 40vw;
						}
				}
			html:not(.ww) #contents {
				white-space: pre-wrap;
/*				text-align: justify; */
			}
				#contents:first-line {
					font-size: 2.6rem;
				}
				html.js #contents:after {
					display: block;
					height: calc(100vh - 6rem);
					content: ' ';
				}
			#pagenum {
				display: none;
				width: 6rem;
				height: 3rem;
				position: fixed;
				bottom: 0;
				left: calc(50vw - 3rem);
				font-size: 1rem;
				text-align: center;
				background: 0;
				opacity: .5;
			}
			@media print {
				html, body {
					height: auto;
				}
				#controls, #pagenum {
					display: none !important;
				}
			}
		</style>
		<title>{$title}</title>
	</head>
	<body>
		<div id="controls">
			<span title="{$words} {$_words}" onclick="swapInfo(this)">{$_rTime} {$rTime} {$_rMinutes}</span>&nbsp;&nbsp;
			<button title="{$_setTheme} (T)" onclick="setTheme()">&#9706;</button>
			<button title="{$_decrSize} (-)" onclick="setSize(-.05)">&#65293;</button>
			<button title="{$_incrSize} (+)" onclick="setSize(+.05)">&#65291;</button>
		</div>
		<div id="book">
			<div id="contents">{$contents}</div>
		</div>
		<button id="pagenum" title="{$_gotoPage} (G)" onclick="gotoPage()" oncontextmenu="setScroll(event)"></button>
	</body>
	<script>
		if (document.documentElement.className != 'js')
			throw 0;

		let
			/* Variables */
			bookWidth,
			fontSize = 1,
			offset,
			freeScroll = 0,
			page,
			pages,
			pageCalc,
			pageTurning,
			theme = 0,
			touchDevice = 'ontouchstart' in window,
			touchStartX,
			touchDeltaX,

			/* Elements */
			book = document.getElementById('book'),
			contents = document.getElementById('contents'),
			pageNum = document.getElementById('pagenum'),

			/* Control Functions */
			swapInfo = (el) => {
				let text = el.textContent;
				el.textContent = el.title;
				el.title = text;
			}
			setScroll = (e) => {
				e.preventDefault();
				if (freeScroll ^= 1)
					pageNum.textContent = '{$_selMode}';
				else
					calcDims();
			},
			setTheme = () => {
				document.body.className = `theme\${theme = (theme + 1) % 3}`;
			},
			setSize = (val) => {
				contents.style.fontSize = (fontSize += val) +'em';
				calcDims();
				turn(page);
			},
			gotoPage = () => {
				touchStartX = null;
				let to = prompt('{$_gotoPage}') - 1;
				if (!isNaN(to) && to > -1)
					turn(to);
			},

			/* Pagination Functions */
			calcDims = () => {
				if (pageTurning)
					return;
				let pos = page && pages ? (page - .5) / pages : 0;
				bookWidth = book.offsetWidth;
				offset = bookWidth / 6;
				pages = Math.round(book.scrollWidth / bookWidth) - 1;
				page = pos ? Math.ceil(pos * pages) : Math.round(scrollX / bookWidth);
				pageNum.textContent = `\${page + 1}/\${pages}`;
				console.log("bookWidth", bookWidth, "| offset", offset, "| pages", pages, "| page", page);
			},
			turn = (to) => {
				page = Math.max(to, 0);
				page = Math.min(page, pages - 1);
				scrollTo({
					left: page * bookWidth,
					behavior: 'smooth'
				});
				pageNum.textContent = `\${page + 1}/\${pages}`;
				turnTimeout();
			},
			turnTimeout = () => { // prevent 'onscroll'; prevent 'onresize', triggered by keyboard popup
				if (pageTurning)
					clearTimeout(pageTurning);
				pageTurning = setTimeout(() => {
					clearTimeout(pageTurning);
					pageTurning = 0;
				}, 400);
			};

		/* Keyboard Navigation */
		document.addEventListener('keydown', (e) => {
			if (freeScroll || e.altKey || e.ctrlKey) return;
			switch (e.key) {
				case 'ArrowUp':
				case 'ArrowLeft':
				case 'PageUp':
					page--;
					break;
				case 'ArrowDown':
				case 'ArrowRight':
				case 'PageDown':
					page++;
					break;
				case 'g':
					gotoPage();
					break;
				case 't':
					setTheme();
					break;
				case '-':
					setSize(-.1);
					break;
				case '+':
				case '=':
					setSize(+.1);
					break;
				default:
					return;
			}
			e.preventDefault();
			turn(page);
		});

		/* Touch Navigation */
		book.addEventListener('touchstart', (e) => {
			if (freeScroll) return;
			e.preventDefault();
			touchStartX = e.changedTouches[0].screenX;
		}, 1);
		book.addEventListener('touchmove', (e) => {
			if (freeScroll) return;
			e.preventDefault();
			touchDeltaX = touchStartX - event.changedTouches[0].screenX;
			scrollTo(page * bookWidth + touchDeltaX, 0);
		}, 1);
		book.addEventListener('touchend', (e) => {
			if (freeScroll) return;
			e.preventDefault();
			touchStartX = null;
			if (touchDeltaX < -offset)
				page--;
			else if (touchDeltaX > offset)
				page++;
			turn(page);
		}, 1);

		/* Wheel Navigation */
		window.addEventListener('wheel', (e) => {
			if (freeScroll) return;
			e.preventDefault();
			if (e.deltaY < 0)
				page--;
			else if (e.deltaY > 0)
				page++;
			turn(page);
		}, { passive: false });

		/* Window Listeners */
		window.addEventListener('scroll', () => {
			if (freeScroll) return;
			if (pageTurning)
				return turnTimeout();
			scrolledTo = Math.min(Math.round(scrollX / bookWidth), pages - 1);
			if (scrolledTo > -1)
				pageNum.textContent = `\${scrolledTo + 1}/\${pages}`;
			if (pageCalc)
				clearTimeout(pageCalc);
			pageCalc = setTimeout(() => {
				if (pageTurning)
					return;
				page = Math.min(Math.round(scrollX / bookWidth), pages - 1);
			}, 200);
		}, { passive: true });
		new ResizeObserver(calcDims).observe(book);

		/* Initialization */
		setTimeout(calcDims, 400);
	</script>
</html>
END;

?>