<?php

error_reporting(0); // E_ALL

$str["en"] = (object)[
	"notFound" => "Contents not found!",
	"wrongWw"  => "Wrong watchword!",
	"enterWw"  => "Enter the watchword",
	"submit"   => "Submit",
	"rTime"    => "Reading time",
	"rMinutes" => "min",
	"words"    => "words",
	"selMode"  => "Selection mode",
	"setTheme" => "Change theme",
	"decrSize" => "Decrease font size",
	"incrSize" => "Increase font size",
	"jumpPage" => "Jump to page"
];
$str["nl"] = (object)[
	"notFound" => "Inhoud niet gevonden!",
	"wrongWw"  => "Wachtwoord is onjuist!",
	"enterWw"  => "Vul het wachtwoord in",
	"submit"   => "Verzenden",
	"rTime"    => "Leestijd",
	"rMinutes" => "min",
	"words"    => "woorden",
	"selMode"  => "Selectiemodus",
	"setTheme" => "Thema",
	"decrSize" => "Kleinere letters",
	"incrSize" => "Grotere letters",
	"jumpPage" => "Naar pagina"
];

$dir       = substr(strtok($_SERVER["REQUEST_URI"], "?"), strlen(dirname($_SERVER["SCRIPT_NAME"])));
$book      = trim($dir, "/") ?: ".";
$config    = "config.php";
$contents  = "$book/contents.";
$font      = "fanwood_text.woff";
$wordsPMin = 250;

if (is_readable($config))
	include $config;
if (is_readable("$book/$config"))
	include "$book/$config";

$lang = $lang ?? substr($_SERVER["HTTP_ACCEPT_LANGUAGE"], 0, 2);
$str  = $str[$lang] ?? $str["en"];

foreach (["md", "txt"] as $ext) {
	if (is_readable($contents . $ext)) {
		$contents .= $ext;
		$format    = $ext;
		break;
	}
}
if (!isset($format))
	exit($str->notFound);

$contents  = file_get_contents($contents);
$title     = preg_replace("/^#+ */", "", strtok($contents, "\n"));

if (isset($watchword)) {
	if (session_status() != PHP_SESSION_ACTIVE)
		session_start([
			"cache_limiter"   => "nocache",
			"cookie_httponly" => 1,
			"cookie_lifetime" => 0,
			"cookie_samesite" => "strict",
			"cookie_secure"   => 1
		]);

	$hash  = hash("sha256", $watchword);
	$ww    = $_POST["ww"] ?? false;
	$match = $ww == $watchword || $_SESSION["ww"] == $hash;

	if ($match)
		$_SESSION["ww"] = $hash;
	else if (!$ww)
		$prompt = "$str->enterWw:";
	else if (!$match)
		$prompt = "$str->wrongWw<br>$str->enterWw:";

	if (isset($prompt))
		$contents = <<<WW
			<form action="//{$_SERVER["HTTP_HOST"]}{$_SERVER["REQUEST_URI"]}" method="post">
				<br>{$prompt}<br>
				<input id="ww" type="password" name="ww" autofocus><br>
				<input id="ok" type="submit" value="{$str->submit}">
			</form>
			<script>
				document.documentElement.className = "ww";	// Disable white-space and JavaScript
				if (location.hash.startsWith("#ww=")) {
					ww.value = decodeURI(location.hash.substr(4));
					ok.click();
				}
			</script>
WW;
}

$counters = "";
$fontData = is_readable($font) ? base64_encode(file_get_contents($font)) : "";

if (!isset($prompt)) {
	$words    = str_word_count($contents);
	$rTime    = round($words / $wordsPMin);
	$counters = $rTime < 1 ? "" : "<span title=\"$words $str->words\" onclick=\"swapInfo(this)\">$str->rTime: $rTime $str->rMinutes</span>";

	if ($format == "md") {
		function getImage($img) {
			$book    = $GLOBALS["book"];
			$caption = $img[1];
			$image   = $img[2];
			if ($float = str_ends_with($caption, "<") ? "left" : (str_ends_with($caption, ">") ? "right" : ""))
				$caption = substr($caption, 0, -2);
			return "<figure class=\"$float\"><img src=\"data:image;base64,"
				. base64_encode(@file_get_contents("$book/$image"))
				."\" onclick=\"pop(this)\"><figcaption>$caption</figcaption></figure>";
		};

		$contents = preg_replace("/_(.+?)_/m", "<i>$1</i>", $contents);
		$contents = preg_replace("/\*(.+?)\*/m", "<b>$1</b>", $contents);
		$contents = preg_replace("/^##\s*(.+?)$\n/m", "<h2>$1</h2>", $contents);
		$contents = preg_replace("/^#\s*(.+?)$\n/m",  "<h1>$1</h1>", $contents);
		$contents = preg_replace("/([^!])\[(.+?)\]\(([^:]+?)\)/", "$1<a href=\"$3\">$2</a>", $contents);
		$contents = preg_replace("/([^!])\[(.+?)\]\((.+?)\)/", "$1<a rel=\"noreferrer\" target=\"_blank\" href=\"$3\">$2</a>", $contents);
		$contents = preg_replace_callback("/!\[(.*?)\]\((.+?)\)/", "getImage", $contents);
	}
	$contents = preg_replace("/(?<!href=\")(https?:\/\/.+?)(\s|$)/", "<a rel=\"noreferrer\" target=\"_blank\" href=\"$1\">$1</a>$2", $contents);
}

echo <<<END
<!DOCTYPE html>
<html>
	<head>
		<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text x='-10' y='80' font-size='88'>📖</text></svg>">
		<meta http-equiv="content-type" content="text/html; charset=UTF-8">
		<meta name="referrer" content="no-referrer">
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
		<noscript>
			<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">
		</noscript>
		<script>
			document.documentElement.className = "js";
		</script>
		<style>
			@font-face {
				font-family: Fanwood;
				src: url(data:application/x-font-woff;base64,{$fontData});
			}
			:root {
				--figurebg: #fdf5e6bb;
				--lightbg:  #ccddee;
				--lighttxt: #000000;
				--linktxt:  #666e77;
				--darkbg:   #141a21;
				--darktxt:  #b0b0b0;
				--whitebg:  #ffffff;
			}
			* {
				margin: 0;
				padding: 0;
				font-family: Fanwood, Garamond, "Times New Roman", Times, serif;
				box-sizing: border-box;
				user-select: none;
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
			a {
				color: var(--linktxt);
				text-decoration: none;
			}
				a:hover {
					border-bottom: .5px solid var(--linktxt);
				}
				a[href*=":"]:before {
					font-size: .8em;
					content: "\\29c9  ";
				}
			button {
				width: 3rem;
				height: 3rem;
				background: 0;
				border: 0;
				outline: 0;
				cursor: pointer;
			}
				button::-moz-focus-inner {
					border: 0;
				}
			figure {
				max-height: calc(100vh - 6rem);
				margin-right: .5em;
				padding: .3rem;
				background: var(--figurebg);
				color: var(--lighttxt);
				font-size: 1rem;
				font-style: italic;
				font-weight: normal;
				text-align: center;
				border-radius: 4px;
				break-inside: avoid-column;
			}
				figure.left {
					max-width: 42%;
					float: left;
				}
				figure.right {
					max-width: 42%;
					margin-left: .5em;
					float: right;
				}
				figure img {
					max-width: 100%;
					max-height: calc(100vh - 9rem);
					cursor: pointer;
				}
			form {
				text-align: center;
			}
			h1, h2 {
				clear: both;
			}
			h1 {
				font-size: 2em;
				font-weight: normal;
			}
			h2 {
				font-size: 1.25em;
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
				user-select: text;
/*				text-align: justify; */
			}
				#contents:first-line {
					font-size: 2em;
				}
				html.js #contents:after {
					display: block;
					height: calc(100vh - 6rem);
					content: " ";
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
					html.js #contents:after {
						display: none;
					}
				#controls, #pagenum {
					display: none !important;
				}
			}
		</style>
		<title>{$title}</title>
	</head>
	<body>
		<script>
			let theme = location.hash[location.hash.startsWith("#t=") ? 3 : false] || 0;
			if (theme)
				document.body.className = `theme\${theme}`;
		</script>
		<div id="controls">
			{$counters}&nbsp;&nbsp;
			<button title="{$str->setTheme} (T)" onclick="setTheme()">&#9706;</button>
			<button title="{$str->decrSize} (-)" onclick="setSize(-1)">&#65293;</button>
			<button title="{$str->incrSize} (+)" onclick="setSize(+1)">&#65291;</button>
		</div>
		<div id="book">
			<div id="contents">{$contents}</div>
		</div>
		<button id="pagenum" title="{$str->jumpPage} (P)" onclick="jumpPage()" oncontextmenu="setScroll(event)"></button>
	</body>
	<script>
		if (document.documentElement.className != "js")
			throw 0;

		let
			/* Variables */
			bookWidth,
			fontSize = 1,
			offset,
			freeScroll = 0,
			isFirefox = navigator.userAgent.indexOf("Firefox") > 0,
			page,
			pages,
			pageCalc,
			pageTurning,
			touchDevice = "ontouchstart" in window,
			touchStartX,
			touchDeltaX = 0,

			/* Elements */
			book = document.getElementById("book"),
			contents = document.getElementById("contents"),
			pageNum = document.getElementById("pagenum"),

			/* Control Functions */
			swapInfo = (el) => {
				let text = el.textContent;
				el.textContent = el.title;
				el.title = text;
			},
			setScroll = (e) => {
				e.preventDefault();
				if (freeScroll ^= 1)
					pageNum.textContent = "{$str->selMode}";
				else
					calcDims();
			},
			setTheme = () => {
				document.body.className = `theme\${theme = (theme + 1) % 3}`;
			},
			setSize = (val) => {
				contents.style.fontSize = (val < 0 && fontSize < .51 ? .5 : fontSize += val / 20) +"em";
				calcDims();
				turn(page);
			},
			jumpPage = () => {
				touchStartX = null;
				let to = prompt("{$str->jumpPage}:") - 1;
				if (!isNaN(to) && to > -1)
					jump(to);
			},
			pop = (img) => {
				let win = window.open("about:blank");
				win.document.write(`
					<meta name="viewport" content="user-scalable=yes">
					<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎨</text></svg>">
					<style>*{max-width:100%; background:black}</style>
					<title>\${document.title}</title>
					<img src="\${img.src}">
				`);
				win.document.close();
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
				console.log("bookWidth", bookWidth, "| offset", offset, "| pages", pages, "| page", page + 1);
			},
			jump = (to) => turn(to, false),
			turn = (to, smooth = true) => {
				page = Math.max(to, 0);
				page = Math.min(page, pages - 1);
				turnTimeout();
				scrollTo({
					left: page * bookWidth,
					behavior: smooth ? "smooth" : "auto"
				});
				pageNum.textContent = `\${page + 1}/\${pages}`;
			},
			turnTimeout = () => { // prevent "onscroll"; prevent "onresize", triggered by keyboard popup
				if (pageTurning)
					clearTimeout(pageTurning);
				pageTurning = setTimeout(() => {
					clearTimeout(pageTurning);
					pageTurning = 0;
				}, 400);
			};

		/* Keyboard Navigation */
		document.addEventListener("keydown", (e) => {
			if (freeScroll || e.altKey || e.ctrlKey) return;
			switch (e.key) {
				case "ArrowUp":
				case "ArrowLeft":
				case "PageUp":
					page--;
					break;
				case "ArrowDown":
				case "ArrowRight":
				case "PageDown":
					page++;
					break;
				case "p":
					jumpPage();
					break;
				case "t":
					setTheme();
					break;
				case "-":
					setSize(-1);
					break;
				case "+":
				case "=":
					setSize(+1);
					break;
				default:
					return;
			}
			e.preventDefault();
			turn(page);
		});

		/* Touch Navigation */
		book.addEventListener("touchstart", (e) => {
			if (freeScroll) return;
			if (!isFirefox || e.target.id == "contents")
				e.preventDefault();
			touchStartX = e.changedTouches[0].screenX;
		}, 1);
		book.addEventListener("touchmove", (e) => {
			if (freeScroll) return;
			e.preventDefault();
			touchDeltaX = touchStartX - event.changedTouches[0].screenX;
			scrollTo(page * bookWidth + touchDeltaX, 0);
		}, 1);
		book.addEventListener("touchend", (e) => {
			if (freeScroll) return;
			if (!isFirefox && e.target.id != "contents" && Math.abs(touchDeltaX) < 10) {
				e.preventDefault();
				e.target.click();
			}
			touchStartX = null;
			if (touchDeltaX < -offset)
				page--;
			else if (touchDeltaX > offset)
				page++;
			touchDeltaX = 0;
			turn(page);
		}, 1);

		/* Wheel Navigation */
		window.addEventListener("wheel", (e) => {
			if (freeScroll) return;
			e.preventDefault();
			if (e.deltaY < 0)
				page--;
			else if (e.deltaY > 0)
				page++;
			turn(page);
		}, { passive: false });

		/* Listeners */
		window.addEventListener("click", (e) => {
			if (!theme) return;
			e = e.target.tagName == "A" ? e.target : e.target.parentElement;	// For codeberg.org/ltguillaume/bread
			if (e.href) {
				e.href = e.getAttribute('href') +`#t=\${theme}`;
				if (location.hash != `#t=\${theme}`)
					location.hash = `#t=\${theme}`;
			}
		});
		window.addEventListener("resize", calcDims);
		window.addEventListener("scroll", () => {
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

		/* Initialization */
		setTimeout(calcDims, 400);
		console.log("Source: https://codeberg.org/ltguillaume/ureader");
	</script>
</html>
END;

?>