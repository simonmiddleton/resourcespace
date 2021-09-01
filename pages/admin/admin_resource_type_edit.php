<?php
/**
 * User edit form display page (part of Team Center)
 * 
 * @package ResourceSpace
 * @subpackage Pages_Team
 */
include "../../include/db.php";

include "../../include/authenticate.php"; 

if (!checkperm("a"))
	{
	exit ("Permission denied.");
	}

$ref                   = getvalescaped('ref', '', true);
$name                  = getvalescaped('name', '');
$config_options        = getvalescaped('config_options', '');
$allowed_extensions    = getvalescaped('allowed_extensions', '');
$tab                   = getvalescaped('tab', '');
$colour                = getvalescaped('colour', 0, true);
$push_metadata         = ('' != getvalescaped('push_metadata', '') ? 1 : 0);
$inherit_global_fields = ('' != getvalescaped('inherit_global_fields', '') ? 1 : 0);
$icon                  = getvalescaped('icon', '');

$font_awesome_icons = array("500px" => "f26e","accessible-icon" => "f368","accusoft" => "f369","acquisitions-incorporated" => "f6af","ad" => "f641","address-book" => "f2b9","address-card" => "f2bb","adjust" => "f042","adn" => "f170","adobe" => "f778","adversal" => "f36a","affiliatetheme" => "f36b","air-freshener" => "f5d0","airbnb" => "f834","algolia" => "f36c","align-center" => "f037","align-justify" => "f039","align-left" => "f036","align-right" => "f038","alipay" => "f642","allergies" => "f461","amazon" => "f270","amazon-pay" => "f42c","ambulance" => "f0f9","american-sign-language-interpreting" => "f2a3","amilia" => "f36d","anchor" => "f13d","android" => "f17b","angellist" => "f209","angle-double-down" => "f103","angle-double-left" => "f100","angle-double-right" => "f101","angle-double-up" => "f102","angle-down" => "f107","angle-left" => "f104","angle-right" => "f105","angle-up" => "f106","angry" => "f556","angrycreative" => "f36e","angular" => "f420","ankh" => "f644","app-store" => "f36f","app-store-ios" => "f370","apper" => "f371","apple" => "f179","apple-alt" => "f5d1","apple-pay" => "f415","archive" => "f187","archway" => "f557","arrow-alt-circle-down" => "f358","arrow-alt-circle-left" => "f359","arrow-alt-circle-right" => "f35a","arrow-alt-circle-up" => "f35b","arrow-circle-down" => "f0ab","arrow-circle-left" => "f0a8","arrow-circle-right" => "f0a9","arrow-circle-up" => "f0aa","arrow-down" => "f063","arrow-left" => "f060","arrow-right" => "f061","arrow-up" => "f062","arrows-alt" => "f0b2","arrows-alt-h" => "f337","arrows-alt-v" => "f338","artstation" => "f77a","assistive-listening-systems" => "f2a2","asterisk" => "f069","asymmetrik" => "f372","at" => "f1fa","atlas" => "f558","atlassian" => "f77b","atom" => "f5d2","audible" => "f373","audio-description" => "f29e","autoprefixer" => "f41c","avianex" => "f374","aviato" => "f421","award" => "f559","aws" => "f375","baby" => "f77c","baby-carriage" => "f77d","backspace" => "f55a","backward" => "f04a","bacon" => "f7e5","bahai" => "f666","balance-scale" => "f24e","balance-scale-left" => "f515","balance-scale-right" => "f516","ban" => "f05e","band-aid" => "f462","bandcamp" => "f2d5","barcode" => "f02a","bars" => "f0c9","baseball-ball" => "f433","basketball-ball" => "f434","bath" => "f2cd","battery-empty" => "f244","battery-full" => "f240","battery-half" => "f242","battery-quarter" => "f243","battery-three-quarters" => "f241","battle-net" => "f835","bed" => "f236","beer" => "f0fc","behance" => "f1b4","behance-square" => "f1b5","bell" => "f0f3","bell-slash" => "f1f6","bezier-curve" => "f55b","bible" => "f647","bicycle" => "f206","biking" => "f84a","bimobject" => "f378","binoculars" => "f1e5","biohazard" => "f780","birthday-cake" => "f1fd","bitbucket" => "f171","bitcoin" => "f379","bity" => "f37a","black-tie" => "f27e","blackberry" => "f37b","blender" => "f517","blender-phone" => "f6b6","blind" => "f29d","blog" => "f781","blogger" => "f37c","blogger-b" => "f37d","bluetooth" => "f293","bluetooth-b" => "f294","bold" => "f032","bolt" => "f0e7","bomb" => "f1e2","bone" => "f5d7","bong" => "f55c","book" => "f02d","book-dead" => "f6b7","book-medical" => "f7e6","book-open" => "f518","book-reader" => "f5da","bookmark" => "f02e","bootstrap" => "f836","border-all" => "f84c","border-none" => "f850","border-style" => "f853","bowling-ball" => "f436","box" => "f466","box-open" => "f49e","box-tissue" => "f95b","boxes" => "f468","braille" => "f2a1","brain" => "f5dc","bread-slice" => "f7ec","briefcase" => "f0b1","briefcase-medical" => "f469","broadcast-tower" => "f519","broom" => "f51a","brush" => "f55d","btc" => "f15a","buffer" => "f837","bug" => "f188","building" => "f1ad","bullhorn" => "f0a1","bullseye" => "f140","burn" => "f46a","buromobelexperte" => "f37f","bus" => "f207","bus-alt" => "f55e","business-time" => "f64a","buy-n-large" => "f8a6","buysellads" => "f20d","calculator" => "f1ec","calendar" => "f133","calendar-alt" => "f073","calendar-check" => "f274","calendar-day" => "f783","calendar-minus" => "f272","calendar-plus" => "f271","calendar-times" => "f273","calendar-week" => "f784","camera" => "f030","camera-retro" => "f083","campground" => "f6bb","canadian-maple-leaf" => "f785","candy-cane" => "f786","cannabis" => "f55f","capsules" => "f46b","car" => "f1b9","car-alt" => "f5de","car-battery" => "f5df","car-crash" => "f5e1","car-side" => "f5e4","caravan" => "f8ff","caret-down" => "f0d7","caret-left" => "f0d9","caret-right" => "f0da","caret-square-down" => "f150","caret-square-left" => "f191","caret-square-right" => "f152","caret-square-up" => "f151","caret-up" => "f0d8","carrot" => "f787","cart-arrow-down" => "f218","cart-plus" => "f217","cash-register" => "f788","cat" => "f6be","cc-amazon-pay" => "f42d","cc-amex" => "f1f3","cc-apple-pay" => "f416","cc-diners-club" => "f24c","cc-discover" => "f1f2","cc-jcb" => "f24b","cc-mastercard" => "f1f1","cc-paypal" => "f1f4","cc-stripe" => "f1f5","cc-visa" => "f1f0","centercode" => "f380","centos" => "f789","certificate" => "f0a3","chair" => "f6c0","chalkboard" => "f51b","chalkboard-teacher" => "f51c","charging-station" => "f5e7","chart-area" => "f1fe","chart-bar" => "f080","chart-line" => "f201","chart-pie" => "f200","check" => "f00c","check-circle" => "f058","check-double" => "f560","check-square" => "f14a","cheese" => "f7ef","chess" => "f439","chess-bishop" => "f43a","chess-board" => "f43c","chess-king" => "f43f","chess-knight" => "f441","chess-pawn" => "f443","chess-queen" => "f445","chess-rook" => "f447","chevron-circle-down" => "f13a","chevron-circle-left" => "f137","chevron-circle-right" => "f138","chevron-circle-up" => "f139","chevron-down" => "f078","chevron-left" => "f053","chevron-right" => "f054","chevron-up" => "f077","child" => "f1ae","chrome" => "f268","chromecast" => "f838","church" => "f51d","circle" => "f111","circle-notch" => "f1ce","city" => "f64f","clinic-medical" => "f7f2","clipboard" => "f328","clipboard-check" => "f46c","clipboard-list" => "f46d","clock" => "f017","clone" => "f24d","closed-captioning" => "f20a","cloud" => "f0c2","cloud-download-alt" => "f381","cloud-meatball" => "f73b","cloud-moon" => "f6c3","cloud-moon-rain" => "f73c","cloud-rain" => "f73d","cloud-showers-heavy" => "f740","cloud-sun" => "f6c4","cloud-sun-rain" => "f743","cloud-upload-alt" => "f382","cloudscale" => "f383","cloudsmith" => "f384","cloudversify" => "f385","cocktail" => "f561","code" => "f121","code-branch" => "f126","codepen" => "f1cb","codiepie" => "f284","coffee" => "f0f4","cog" => "f013","cogs" => "f085","coins" => "f51e","columns" => "f0db","comment" => "f075","comment-alt" => "f27a","comment-dollar" => "f651","comment-dots" => "f4ad","comment-medical" => "f7f5","comment-slash" => "f4b3","comments" => "f086","comments-dollar" => "f653","compact-disc" => "f51f","compass" => "f14e","compress" => "f066","compress-alt" => "f422","compress-arrows-alt" => "f78c","concierge-bell" => "f562","confluence" => "f78d","connectdevelop" => "f20e","contao" => "f26d","cookie" => "f563","cookie-bite" => "f564","copy" => "f0c5","copyright" => "f1f9","cotton-bureau" => "f89e","couch" => "f4b8","cpanel" => "f388","creative-commons" => "f25e","creative-commons-by" => "f4e7","creative-commons-nc" => "f4e8","creative-commons-nc-eu" => "f4e9","creative-commons-nc-jp" => "f4ea","creative-commons-nd" => "f4eb","creative-commons-pd" => "f4ec","creative-commons-pd-alt" => "f4ed","creative-commons-remix" => "f4ee","creative-commons-sa" => "f4ef","creative-commons-sampling" => "f4f0","creative-commons-sampling-plus" => "f4f1","creative-commons-share" => "f4f2","creative-commons-zero" => "f4f3","credit-card" => "f09d","critical-role" => "f6c9","crop" => "f125","crop-alt" => "f565","cross" => "f654","crosshairs" => "f05b","crow" => "f520","crown" => "f521","crutch" => "f7f7","css3" => "f13c","css3-alt" => "f38b","cube" => "f1b2","cubes" => "f1b3","cut" => "f0c4","cuttlefish" => "f38c","d-and-d" => "f38d","d-and-d-beyond" => "f6ca","dailymotion" => "f952","dashcube" => "f210","database" => "f1c0","deaf" => "f2a4","delicious" => "f1a5","democrat" => "f747","deploydog" => "f38e","deskpro" => "f38f","desktop" => "f108","dev" => "f6cc","deviantart" => "f1bd","dharmachakra" => "f655","dhl" => "f790","diagnoses" => "f470","diaspora" => "f791","dice" => "f522","dice-d20" => "f6cf","dice-d6" => "f6d1","dice-five" => "f523","dice-four" => "f524","dice-one" => "f525","dice-six" => "f526","dice-three" => "f527","dice-two" => "f528","digg" => "f1a6","digital-ocean" => "f391","digital-tachograph" => "f566","directions" => "f5eb","discord" => "f392","discourse" => "f393","disease" => "f7fa","divide" => "f529","dizzy" => "f567","dna" => "f471","dochub" => "f394","docker" => "f395","dog" => "f6d3","dollar-sign" => "f155","dolly" => "f472","dolly-flatbed" => "f474","donate" => "f4b9","door-closed" => "f52a","door-open" => "f52b","dot-circle" => "f192","dove" => "f4ba","download" => "f019","draft2digital" => "f396","drafting-compass" => "f568","dragon" => "f6d5","draw-polygon" => "f5ee","dribbble" => "f17d","dribbble-square" => "f397","dropbox" => "f16b","drum" => "f569","drum-steelpan" => "f56a","drumstick-bite" => "f6d7","drupal" => "f1a9","dumbbell" => "f44b","dumpster" => "f793","dumpster-fire" => "f794","dungeon" => "f6d9","dyalog" => "f399","earlybirds" => "f39a","ebay" => "f4f4","edge" => "f282","edit" => "f044","egg" => "f7fb","eject" => "f052","elementor" => "f430","ellipsis-h" => "f141","ellipsis-v" => "f142","ello" => "f5f1","ember" => "f423","empire" => "f1d1","envelope" => "f0e0","envelope-open" => "f2b6","envelope-open-text" => "f658","envelope-square" => "f199","envira" => "f299","equals" => "f52c","eraser" => "f12d","erlang" => "f39d","ethereum" => "f42e","ethernet" => "f796","etsy" => "f2d7","euro-sign" => "f153","evernote" => "f839","exchange-alt" => "f362","exclamation" => "f12a","exclamation-circle" => "f06a","exclamation-triangle" => "f071","expand" => "f065","expand-alt" => "f424","expand-arrows-alt" => "f31e","expeditedssl" => "f23e","external-link-alt" => "f35d","external-link-square-alt" => "f360","eye" => "f06e","eye-dropper" => "f1fb","eye-slash" => "f070","facebook" => "f09a","facebook-f" => "f39e","facebook-messenger" => "f39f","facebook-square" => "f082","fan" => "f863","fantasy-flight-games" => "f6dc","fast-backward" => "f049","fast-forward" => "f050","faucet" => "f905","fax" => "f1ac","feather" => "f52d","feather-alt" => "f56b","fedex" => "f797","fedora" => "f798","female" => "f182","fighter-jet" => "f0fb","figma" => "f799","file" => "f15b","file-alt" => "f15c","file-archive" => "f1c6","file-audio" => "f1c7","file-code" => "f1c9","file-contract" => "f56c","file-csv" => "f6dd","file-download" => "f56d","file-excel" => "f1c3","file-export" => "f56e","file-image" => "f1c5","file-import" => "f56f","file-invoice" => "f570","file-invoice-dollar" => "f571","file-medical" => "f477","file-medical-alt" => "f478","file-pdf" => "f1c1","file-powerpoint" => "f1c4","file-prescription" => "f572","file-signature" => "f573","file-upload" => "f574","file-video" => "f1c8","file-word" => "f1c2","fill" => "f575","fill-drip" => "f576","film" => "f008","filter" => "f0b0","fingerprint" => "f577","fire" => "f06d","fire-alt" => "f7e4","fire-extinguisher" => "f134","firefox" => "f269","firefox-browser" => "f907","first-aid" => "f479","first-order" => "f2b0","first-order-alt" => "f50a","firstdraft" => "f3a1","fish" => "f578","fist-raised" => "f6de","flag" => "f024","flag-checkered" => "f11e","flag-usa" => "f74d","flask" => "f0c3","flickr" => "f16e","flipboard" => "f44d","flushed" => "f579","fly" => "f417","folder" => "f07b","folder-minus" => "f65d","folder-open" => "f07c","folder-plus" => "f65e","font" => "f031","font-awesome" => "f2b4","font-awesome-alt" => "f35c","font-awesome-flag" => "f425","font-awesome-logo-full" => "f4e6","fonticons" => "f280","fonticons-fi" => "f3a2","football-ball" => "f44e","fort-awesome" => "f286","fort-awesome-alt" => "f3a3","forumbee" => "f211","forward" => "f04e","foursquare" => "f180","free-code-camp" => "f2c5","freebsd" => "f3a4","frog" => "f52e","frown" => "f119","frown-open" => "f57a","fulcrum" => "f50b","funnel-dollar" => "f662","futbol" => "f1e3","galactic-republic" => "f50c","galactic-senate" => "f50d","gamepad" => "f11b","gas-pump" => "f52f","gavel" => "f0e3","gem" => "f3a5","genderless" => "f22d","get-pocket" => "f265","gg" => "f260","gg-circle" => "f261","ghost" => "f6e2","gift" => "f06b","gifts" => "f79c","git" => "f1d3","git-alt" => "f841","git-square" => "f1d2","github" => "f09b","github-alt" => "f113","github-square" => "f092","gitkraken" => "f3a6","gitlab" => "f296","gitter" => "f426","glass-cheers" => "f79f","glass-martini" => "f000","glass-martini-alt" => "f57b","glass-whiskey" => "f7a0","glasses" => "f530","glide" => "f2a5","glide-g" => "f2a6","globe" => "f0ac","globe-africa" => "f57c","globe-americas" => "f57d","globe-asia" => "f57e","globe-europe" => "f7a2","gofore" => "f3a7","golf-ball" => "f450","goodreads" => "f3a8","goodreads-g" => "f3a9","google" => "f1a0","google-drive" => "f3aa","google-play" => "f3ab","google-plus" => "f2b3","google-plus-g" => "f0d5","google-plus-square" => "f0d4","google-wallet" => "f1ee","gopuram" => "f664","graduation-cap" => "f19d","gratipay" => "f184","grav" => "f2d6","greater-than" => "f531","greater-than-equal" => "f532","grimace" => "f57f","grin" => "f580","grin-alt" => "f581","grin-beam" => "f582","grin-beam-sweat" => "f583","grin-hearts" => "f584","grin-squint" => "f585","grin-squint-tears" => "f586","grin-stars" => "f587","grin-tears" => "f588","grin-tongue" => "f589","grin-tongue-squint" => "f58a","grin-tongue-wink" => "f58b","grin-wink" => "f58c","grip-horizontal" => "f58d","grip-lines" => "f7a4","grip-lines-vertical" => "f7a5","grip-vertical" => "f58e","gripfire" => "f3ac","grunt" => "f3ad","guitar" => "f7a6","gulp" => "f3ae","h-square" => "f0fd","hacker-news" => "f1d4","hacker-news-square" => "f3af","hackerrank" => "f5f7","hamburger" => "f805","hammer" => "f6e3","hamsa" => "f665","hand-holding" => "f4bd","hand-holding-heart" => "f4be","hand-holding-medical" => "f95c","hand-holding-usd" => "f4c0","hand-holding-water" => "f4c1","hand-lizard" => "f258","hand-middle-finger" => "f806","hand-paper" => "f256","hand-peace" => "f25b","hand-point-down" => "f0a7","hand-point-left" => "f0a5","hand-point-right" => "f0a4","hand-point-up" => "f0a6","hand-pointer" => "f25a","hand-rock" => "f255","hand-scissors" => "f257","hand-sparkles" => "f95d","hand-spock" => "f259","hands" => "f4c2","hands-helping" => "f4c4","hands-wash" => "f95e","handshake" => "f2b5","handshake-alt-slash" => "f95f","handshake-slash" => "f960","hanukiah" => "f6e6","hard-hat" => "f807","hashtag" => "f292","hat-cowboy" => "f8c0","hat-cowboy-side" => "f8c1","hat-wizard" => "f6e8","hdd" => "f0a0","head-side-cough" => "f961","head-side-cough-slash" => "f962","head-side-mask" => "f963","head-side-virus" => "f964","heading" => "f1dc","headphones" => "f025","headphones-alt" => "f58f","headset" => "f590","heart" => "f004","heart-broken" => "f7a9","heartbeat" => "f21e","helicopter" => "f533","highlighter" => "f591","hiking" => "f6ec","hippo" => "f6ed","hips" => "f452","hire-a-helper" => "f3b0","history" => "f1da","hockey-puck" => "f453","holly-berry" => "f7aa","home" => "f015","hooli" => "f427","hornbill" => "f592","horse" => "f6f0","horse-head" => "f7ab","hospital" => "f0f8","hospital-alt" => "f47d","hospital-symbol" => "f47e","hospital-user" => "f80d","hot-tub" => "f593","hotdog" => "f80f","hotel" => "f594","hotjar" => "f3b1","hourglass" => "f254","hourglass-end" => "f253","hourglass-half" => "f252","hourglass-start" => "f251","house-damage" => "f6f1","house-user" => "f965","houzz" => "f27c","hryvnia" => "f6f2","html5" => "f13b","hubspot" => "f3b2","i-cursor" => "f246","ice-cream" => "f810","icicles" => "f7ad","icons" => "f86d","id-badge" => "f2c1","id-card" => "f2c2","id-card-alt" => "f47f","ideal" => "f913","igloo" => "f7ae","image" => "f03e","images" => "f302","imdb" => "f2d8","inbox" => "f01c","indent" => "f03c","industry" => "f275","infinity" => "f534","info" => "f129","info-circle" => "f05a","instagram" => "f16d","instagram-square" => "f955","intercom" => "f7af","internet-explorer" => "f26b","invision" => "f7b0","ioxhost" => "f208","italic" => "f033","itch-io" => "f83a","itunes" => "f3b4","itunes-note" => "f3b5","java" => "f4e4","jedi" => "f669","jedi-order" => "f50e","jenkins" => "f3b6","jira" => "f7b1","joget" => "f3b7","joint" => "f595","joomla" => "f1aa","journal-whills" => "f66a","js" => "f3b8","js-square" => "f3b9","jsfiddle" => "f1cc","kaaba" => "f66b","kaggle" => "f5fa","key" => "f084","keybase" => "f4f5","keyboard" => "f11c","keycdn" => "f3ba","khanda" => "f66d","kickstarter" => "f3bb","kickstarter-k" => "f3bc","kiss" => "f596","kiss-beam" => "f597","kiss-wink-heart" => "f598","kiwi-bird" => "f535","korvue" => "f42f","landmark" => "f66f","language" => "f1ab","laptop" => "f109","laptop-code" => "f5fc","laptop-house" => "f966","laptop-medical" => "f812","laravel" => "f3bd","lastfm" => "f202","lastfm-square" => "f203","laugh" => "f599","laugh-beam" => "f59a","laugh-squint" => "f59b","laugh-wink" => "f59c","layer-group" => "f5fd","leaf" => "f06c","leanpub" => "f212","lemon" => "f094","less" => "f41d","less-than" => "f536","less-than-equal" => "f537","level-down-alt" => "f3be","level-up-alt" => "f3bf","life-ring" => "f1cd","lightbulb" => "f0eb","line" => "f3c0","link" => "f0c1","linkedin" => "f08c","linkedin-in" => "f0e1","linode" => "f2b8","linux" => "f17c","lira-sign" => "f195","list" => "f03a","list-alt" => "f022","list-ol" => "f0cb","list-ul" => "f0ca","location-arrow" => "f124","lock" => "f023","lock-open" => "f3c1","long-arrow-alt-down" => "f309","long-arrow-alt-left" => "f30a","long-arrow-alt-right" => "f30b","long-arrow-alt-up" => "f30c","low-vision" => "f2a8","luggage-cart" => "f59d","lungs" => "f604","lungs-virus" => "f967","lyft" => "f3c3","magento" => "f3c4","magic" => "f0d0","magnet" => "f076","mail-bulk" => "f674","mailchimp" => "f59e","male" => "f183","mandalorian" => "f50f","map" => "f279","map-marked" => "f59f","map-marked-alt" => "f5a0","map-marker" => "f041","map-marker-alt" => "f3c5","map-pin" => "f276","map-signs" => "f277","markdown" => "f60f","marker" => "f5a1","mars" => "f222","mars-double" => "f227","mars-stroke" => "f229","mars-stroke-h" => "f22b","mars-stroke-v" => "f22a","mask" => "f6fa","mastodon" => "f4f6","maxcdn" => "f136","mdb" => "f8ca","medal" => "f5a2","medapps" => "f3c6","medium" => "f23a","medium-m" => "f3c7","medkit" => "f0fa","medrt" => "f3c8","meetup" => "f2e0","megaport" => "f5a3","meh" => "f11a","meh-blank" => "f5a4","meh-rolling-eyes" => "f5a5","memory" => "f538","mendeley" => "f7b3","menorah" => "f676","mercury" => "f223","meteor" => "f753","microblog" => "f91a","microchip" => "f2db","microphone" => "f130","microphone-alt" => "f3c9","microphone-alt-slash" => "f539","microphone-slash" => "f131","microscope" => "f610","microsoft" => "f3ca","minus" => "f068","minus-circle" => "f056","minus-square" => "f146","mitten" => "f7b5","mix" => "f3cb","mixcloud" => "f289","mixer" => "f956","mizuni" => "f3cc","mobile" => "f10b","mobile-alt" => "f3cd","modx" => "f285","monero" => "f3d0","money-bill" => "f0d6","money-bill-alt" => "f3d1","money-bill-wave" => "f53a","money-bill-wave-alt" => "f53b","money-check" => "f53c","money-check-alt" => "f53d","monument" => "f5a6","moon" => "f186","mortar-pestle" => "f5a7","mosque" => "f678","motorcycle" => "f21c","mountain" => "f6fc","mouse" => "f8cc","mouse-pointer" => "f245","mug-hot" => "f7b6","music" => "f001","napster" => "f3d2","neos" => "f612","network-wired" => "f6ff","neuter" => "f22c","newspaper" => "f1ea","nimblr" => "f5a8","node" => "f419","node-js" => "f3d3","not-equal" => "f53e","notes-medical" => "f481","npm" => "f3d4","ns8" => "f3d5","nutritionix" => "f3d6","object-group" => "f247","object-ungroup" => "f248","odnoklassniki" => "f263","odnoklassniki-square" => "f264","oil-can" => "f613","old-republic" => "f510","om" => "f679","opencart" => "f23d","openid" => "f19b","opera" => "f26a","optin-monster" => "f23c","orcid" => "f8d2","osi" => "f41a","otter" => "f700","outdent" => "f03b","page4" => "f3d7","pagelines" => "f18c","pager" => "f815","paint-brush" => "f1fc","paint-roller" => "f5aa","palette" => "f53f","palfed" => "f3d8","pallet" => "f482","paper-plane" => "f1d8","paperclip" => "f0c6","parachute-box" => "f4cd","paragraph" => "f1dd","parking" => "f540","passport" => "f5ab","pastafarianism" => "f67b","paste" => "f0ea","patreon" => "f3d9","pause" => "f04c","pause-circle" => "f28b","paw" => "f1b0","paypal" => "f1ed","peace" => "f67c","pen" => "f304","pen-alt" => "f305","pen-fancy" => "f5ac","pen-nib" => "f5ad","pen-square" => "f14b","pencil-alt" => "f303","pencil-ruler" => "f5ae","penny-arcade" => "f704","people-arrows" => "f968","people-carry" => "f4ce","pepper-hot" => "f816","percent" => "f295","percentage" => "f541","periscope" => "f3da","person-booth" => "f756","phabricator" => "f3db","phoenix-framework" => "f3dc","phoenix-squadron" => "f511","phone" => "f095","phone-alt" => "f879","phone-slash" => "f3dd","phone-square" => "f098","phone-square-alt" => "f87b","phone-volume" => "f2a0","photo-video" => "f87c","php" => "f457","pied-piper" => "f2ae","pied-piper-alt" => "f1a8","pied-piper-hat" => "f4e5","pied-piper-pp" => "f1a7","pied-piper-square" => "f91e","piggy-bank" => "f4d3","pills" => "f484","pinterest" => "f0d2","pinterest-p" => "f231","pinterest-square" => "f0d3","pizza-slice" => "f818","place-of-worship" => "f67f","plane" => "f072","plane-arrival" => "f5af","plane-departure" => "f5b0","plane-slash" => "f969","play" => "f04b","play-circle" => "f144","playstation" => "f3df","plug" => "f1e6","plus" => "f067","plus-circle" => "f055","plus-square" => "f0fe","podcast" => "f2ce","poll" => "f681","poll-h" => "f682","poo" => "f2fe","poo-storm" => "f75a","poop" => "f619","portrait" => "f3e0","pound-sign" => "f154","power-off" => "f011","pray" => "f683","praying-hands" => "f684","prescription" => "f5b1","prescription-bottle" => "f485","prescription-bottle-alt" => "f486","print" => "f02f","procedures" => "f487","product-hunt" => "f288","project-diagram" => "f542","pump-medical" => "f96a","pump-soap" => "f96b","pushed" => "f3e1","puzzle-piece" => "f12e","python" => "f3e2","qq" => "f1d6","qrcode" => "f029","question" => "f128","question-circle" => "f059","quidditch" => "f458","quinscape" => "f459","quora" => "f2c4","quote-left" => "f10d","quote-right" => "f10e","quran" => "f687","r-project" => "f4f7","radiation" => "f7b9","radiation-alt" => "f7ba","rainbow" => "f75b","random" => "f074","raspberry-pi" => "f7bb","ravelry" => "f2d9","react" => "f41b","reacteurope" => "f75d","readme" => "f4d5","rebel" => "f1d0","receipt" => "f543","record-vinyl" => "f8d9","recycle" => "f1b8","red-river" => "f3e3","reddit" => "f1a1","reddit-alien" => "f281","reddit-square" => "f1a2","redhat" => "f7bc","redo" => "f01e","redo-alt" => "f2f9","registered" => "f25d","remove-format" => "f87d","renren" => "f18b","reply" => "f3e5","reply-all" => "f122","replyd" => "f3e6","republican" => "f75e","researchgate" => "f4f8","resolving" => "f3e7","restroom" => "f7bd","retweet" => "f079","rev" => "f5b2","ribbon" => "f4d6","ring" => "f70b","road" => "f018","robot" => "f544","rocket" => "f135","rocketchat" => "f3e8","rockrms" => "f3e9","route" => "f4d7","rss" => "f09e","rss-square" => "f143","ruble-sign" => "f158","ruler" => "f545","ruler-combined" => "f546","ruler-horizontal" => "f547","ruler-vertical" => "f548","running" => "f70c","rupee-sign" => "f156","sad-cry" => "f5b3","sad-tear" => "f5b4","safari" => "f267","salesforce" => "f83b","sass" => "f41e","satellite" => "f7bf","satellite-dish" => "f7c0","save" => "f0c7","schlix" => "f3ea","school" => "f549","screwdriver" => "f54a","scribd" => "f28a","scroll" => "f70e","sd-card" => "f7c2","search" => "f002","search-dollar" => "f688","search-location" => "f689","search-minus" => "f010","search-plus" => "f00e","searchengin" => "f3eb","seedling" => "f4d8","sellcast" => "f2da","sellsy" => "f213","server" => "f233","servicestack" => "f3ec","shapes" => "f61f","share" => "f064","share-alt" => "f1e0","share-alt-square" => "f1e1","share-square" => "f14d","shekel-sign" => "f20b","shield-alt" => "f3ed","shield-virus" => "f96c","ship" => "f21a","shipping-fast" => "f48b","shirtsinbulk" => "f214","shoe-prints" => "f54b","shopify" => "f957","shopping-bag" => "f290","shopping-basket" => "f291","shopping-cart" => "f07a","shopware" => "f5b5","shower" => "f2cc","shuttle-van" => "f5b6","sign" => "f4d9","sign-in-alt" => "f2f6","sign-language" => "f2a7","sign-out-alt" => "f2f5","signal" => "f012","signature" => "f5b7","sim-card" => "f7c4","simplybuilt" => "f215","sistrix" => "f3ee","sitemap" => "f0e8","sith" => "f512","skating" => "f7c5","sketch" => "f7c6","skiing" => "f7c9","skiing-nordic" => "f7ca","skull" => "f54c","skull-crossbones" => "f714","skyatlas" => "f216","skype" => "f17e","slack" => "f198","slack-hash" => "f3ef","slash" => "f715","sleigh" => "f7cc","sliders-h" => "f1de","slideshare" => "f1e7","smile" => "f118","smile-beam" => "f5b8","smile-wink" => "f4da","smog" => "f75f","smoking" => "f48d","smoking-ban" => "f54d","sms" => "f7cd","snapchat" => "f2ab","snapchat-ghost" => "f2ac","snapchat-square" => "f2ad","snowboarding" => "f7ce","snowflake" => "f2dc","snowman" => "f7d0","snowplow" => "f7d2","soap" => "f96e","socks" => "f696","solar-panel" => "f5ba","sort" => "f0dc","sort-alpha-down" => "f15d","sort-alpha-down-alt" => "f881","sort-alpha-up" => "f15e","sort-alpha-up-alt" => "f882","sort-amount-down" => "f160","sort-amount-down-alt" => "f884","sort-amount-up" => "f161","sort-amount-up-alt" => "f885","sort-down" => "f0dd","sort-numeric-down" => "f162","sort-numeric-down-alt" => "f886","sort-numeric-up" => "f163","sort-numeric-up-alt" => "f887","sort-up" => "f0de","soundcloud" => "f1be","sourcetree" => "f7d3","spa" => "f5bb","space-shuttle" => "f197","speakap" => "f3f3","speaker-deck" => "f83c","spell-check" => "f891","spider" => "f717","spinner" => "f110","splotch" => "f5bc","spotify" => "f1bc","spray-can" => "f5bd","square" => "f0c8","square-full" => "f45c","square-root-alt" => "f698","squarespace" => "f5be","stack-exchange" => "f18d","stack-overflow" => "f16c","stackpath" => "f842","stamp" => "f5bf","star" => "f005","star-and-crescent" => "f699","star-half" => "f089","star-half-alt" => "f5c0","star-of-david" => "f69a","star-of-life" => "f621","staylinked" => "f3f5","steam" => "f1b6","steam-square" => "f1b7","steam-symbol" => "f3f6","step-backward" => "f048","step-forward" => "f051","stethoscope" => "f0f1","sticker-mule" => "f3f7","sticky-note" => "f249","stop" => "f04d","stop-circle" => "f28d","stopwatch" => "f2f2","stopwatch-20" => "f96f","store" => "f54e","store-alt" => "f54f","store-alt-slash" => "f970","store-slash" => "f971","strava" => "f428","stream" => "f550","street-view" => "f21d","strikethrough" => "f0cc","stripe" => "f429","stripe-s" => "f42a","stroopwafel" => "f551","studiovinari" => "f3f8","stumbleupon" => "f1a4","stumbleupon-circle" => "f1a3","subscript" => "f12c","subway" => "f239","suitcase" => "f0f2","suitcase-rolling" => "f5c1","sun" => "f185","superpowers" => "f2dd","superscript" => "f12b","supple" => "f3f9","surprise" => "f5c2","suse" => "f7d6","swatchbook" => "f5c3","swift" => "f8e1","swimmer" => "f5c4","swimming-pool" => "f5c5","symfony" => "f83d","synagogue" => "f69b","sync" => "f021","sync-alt" => "f2f1","syringe" => "f48e","table" => "f0ce","table-tennis" => "f45d","tablet" => "f10a","tablet-alt" => "f3fa","tablets" => "f490","tachometer-alt" => "f3fd","tag" => "f02b","tags" => "f02c","tape" => "f4db","tasks" => "f0ae","taxi" => "f1ba","teamspeak" => "f4f9","teeth" => "f62e","teeth-open" => "f62f","telegram" => "f2c6","telegram-plane" => "f3fe","temperature-high" => "f769","temperature-low" => "f76b","tencent-weibo" => "f1d5","tenge" => "f7d7","terminal" => "f120","text-height" => "f034","text-width" => "f035","th" => "f00a","th-large" => "f009","th-list" => "f00b","the-red-yeti" => "f69d","theater-masks" => "f630","themeco" => "f5c6","themeisle" => "f2b2","thermometer" => "f491","thermometer-empty" => "f2cb","thermometer-full" => "f2c7","thermometer-half" => "f2c9","thermometer-quarter" => "f2ca","thermometer-three-quarters" => "f2c8","think-peaks" => "f731","thumbs-down" => "f165","thumbs-up" => "f164","thumbtack" => "f08d","ticket-alt" => "f3ff","times" => "f00d","times-circle" => "f057","tint" => "f043","tint-slash" => "f5c7","tired" => "f5c8","toggle-off" => "f204","toggle-on" => "f205","toilet" => "f7d8","toilet-paper" => "f71e","toilet-paper-slash" => "f972","toolbox" => "f552","tools" => "f7d9","tooth" => "f5c9","torah" => "f6a0","torii-gate" => "f6a1","tractor" => "f722","trade-federation" => "f513","trademark" => "f25c","traffic-light" => "f637","trailer" => "f941","train" => "f238","tram" => "f7da","transgender" => "f224","transgender-alt" => "f225","trash" => "f1f8","trash-alt" => "f2ed","trash-restore" => "f829","trash-restore-alt" => "f82a","tree" => "f1bb","trello" => "f181","tripadvisor" => "f262","trophy" => "f091","truck" => "f0d1","truck-loading" => "f4de","truck-monster" => "f63b","truck-moving" => "f4df","truck-pickup" => "f63c","tshirt" => "f553","tty" => "f1e4","tumblr" => "f173","tumblr-square" => "f174","tv" => "f26c","twitch" => "f1e8","twitter" => "f099","twitter-square" => "f081","typo3" => "f42b","uber" => "f402","ubuntu" => "f7df","uikit" => "f403","umbraco" => "f8e8","umbrella" => "f0e9","umbrella-beach" => "f5ca","underline" => "f0cd","undo" => "f0e2","undo-alt" => "f2ea","uniregistry" => "f404","unity" => "f949","universal-access" => "f29a","university" => "f19c","unlink" => "f127","unlock" => "f09c","unlock-alt" => "f13e","untappd" => "f405","upload" => "f093","ups" => "f7e0","usb" => "f287","user" => "f007","user-alt" => "f406","user-alt-slash" => "f4fa","user-astronaut" => "f4fb","user-check" => "f4fc","user-circle" => "f2bd","user-clock" => "f4fd","user-cog" => "f4fe","user-edit" => "f4ff","user-friends" => "f500","user-graduate" => "f501","user-injured" => "f728","user-lock" => "f502","user-md" => "f0f0","user-minus" => "f503","user-ninja" => "f504","user-nurse" => "f82f","user-plus" => "f234","user-secret" => "f21b","user-shield" => "f505","user-slash" => "f506","user-tag" => "f507","user-tie" => "f508","user-times" => "f235","users" => "f0c0","users-cog" => "f509","usps" => "f7e1","ussunnah" => "f407","utensil-spoon" => "f2e5","utensils" => "f2e7","vaadin" => "f408","vector-square" => "f5cb","venus" => "f221","venus-double" => "f226","venus-mars" => "f228","viacoin" => "f237","viadeo" => "f2a9","viadeo-square" => "f2aa","vial" => "f492","vials" => "f493","viber" => "f409","video" => "f03d","video-slash" => "f4e2","vihara" => "f6a7","vimeo" => "f40a","vimeo-square" => "f194","vimeo-v" => "f27d","vine" => "f1ca","virus" => "f974","virus-slash" => "f975","viruses" => "f976","vk" => "f189","vnv" => "f40b","voicemail" => "f897","volleyball-ball" => "f45f","volume-down" => "f027","volume-mute" => "f6a9","volume-off" => "f026","volume-up" => "f028","vote-yea" => "f772","vr-cardboard" => "f729","vuejs" => "f41f","walking" => "f554","wallet" => "f555","warehouse" => "f494","water" => "f773","wave-square" => "f83e","waze" => "f83f","weebly" => "f5cc","weibo" => "f18a","weight" => "f496","weight-hanging" => "f5cd","weixin" => "f1d7","whatsapp" => "f232","whatsapp-square" => "f40c","wheelchair" => "f193","whmcs" => "f40d","wifi" => "f1eb","wikipedia-w" => "f266","wind" => "f72e","window-close" => "f410","window-maximize" => "f2d0","window-minimize" => "f2d1","window-restore" => "f2d2","windows" => "f17a","wine-bottle" => "f72f","wine-glass" => "f4e3","wine-glass-alt" => "f5ce","wix" => "f5cf","wizards-of-the-coast" => "f730","wolf-pack-battalion" => "f514","won-sign" => "f159","wordpress" => "f19a","wordpress-simple" => "f411","wpbeginner" => "f297","wpexplorer" => "f2de","wpforms" => "f298","wpressr" => "f3e4","wrench" => "f0ad","x-ray" => "f497","xbox" => "f412","xing" => "f168","xing-square" => "f169","y-combinator" => "f23b","yahoo" => "f19e","yammer" => "f840","yandex" => "f413","yandex-international" => "f414","yarn" => "f7e3","yelp" => "f1e9","yen-sign" => "f157","yin-yang" => "f6ad","yoast" => "f2b1","youtube" => "f167","youtube-square" => "f431","zhihu" => "f63f");

$restype_order_by=getvalescaped("restype_order_by","rt");
$restype_sort=getvalescaped("restype_sort","asc");

$url_params = array("ref"=>$ref,
		    "restype_order_by"=>$restype_order_by,
		    "restype_sort"=>$restype_sort);
$url=generateURL($baseurl . "/pages/admin/admin_resource_type_edit.php",$url_params);

$backurl=getvalescaped("backurl","");
if($backurl=="")
    {
    $backurl=$baseurl . "/pages/admin/admin_resource_types.php?ref=" . $ref;
    }

if (getval("save","")!="" && enforcePostRequest(false))
    {
    # Save resource type data
    log_activity(null,LOG_CODE_EDITED,$name,'resource_type','name',$ref);
    log_activity(null,LOG_CODE_EDITED,$config_options,'resource_type','config_options',$ref);
    log_activity(null,LOG_CODE_EDITED,$allowed_extensions,'resource_type','allowed_extensions',$ref);
    log_activity(null,LOG_CODE_EDITED,$tab,'resource_type','tab_name',$ref);

    if ($execution_lockout) {$config_options="";} # Not allowed to save PHP if execution_lockout set.
        
    sql_query("
        UPDATE resource_type
           SET `name`= '{$name}',
               config_options = '{$config_options}',
               allowed_extensions = '{$allowed_extensions}',
               tab_name = '{$tab}',
               push_metadata = '{$push_metadata}',
               inherit_global_fields = '{$inherit_global_fields}',
               colour = '{$colour}',
               icon = '{$icon}'
         WHERE ref = '$ref'
     ");
    clear_query_cache("schema");

    redirect(generateURL($baseurl_short . "pages/admin/admin_resource_types.php",$url_params));
    }


$confirm_delete = false;
$confirm_move_associated_rtf = false;
if(getval("delete", "") != "" && enforcePostRequest(false))
    {
    $targettype=getvalescaped("targettype","");
    $prereq_action = getval("prereq_action", "");
    $affectedresources=sql_array("select ref value from resource where resource_type='$ref' and ref>0",0);
    $affected_rtfs = get_resource_type_fields(array($ref), "ref", "asc", "", array(), true);
    if(count($affectedresources)>0 && $targettype=="")
        {
        //User needs to confirm a new resource type
        $confirm_delete=true;
        }
    else if(count($affected_rtfs) > 0 && $targettype == "")
        {
        $confirm_move_associated_rtf = true;
        }
    else
        {
        //If we have a target type, move the current resources to the new resource type
        if($targettype!="" && $targettype!=$ref)
            {
            if($prereq_action == "move_affected_resources")
                {
                foreach($affectedresources as $affectedresource)
                    {
                    update_resource_type($affectedresource,$targettype);
                    }
                }

            if($prereq_action == "move_affected_rtfs")
                {
                foreach($affected_rtfs as $affected_rtf)
                    {
                    sql_query("UPDATE resource_type_field SET resource_type = '{$targettype}' WHERE ref = '{$affected_rtf['ref']}'");
                    clear_query_cache("schema");
                    }
                }
            }

        $affectedresources = sql_array("SELECT ref AS value FROM resource WHERE resource_type = '$ref' AND ref > 0", 0);
        $affected_rtfs = get_resource_type_fields(array($ref), "ref", "asc", "", array(), true);
        if(count($affectedresources) === 0 && count($affected_rtfs) === 0)
            {
            sql_query("delete from resource_type where ref='$ref'");
            clear_query_cache("schema");
            redirect(generateURL($baseurl_short . "pages/admin/admin_resource_types.php",$url_params));
            }
        }
    }
$actions_required = ($confirm_delete || $confirm_move_associated_rtf);

# Fetch  data
$restypedata=sql_query ("
      SELECT ref,
             name,
             order_by,
             config_options,
             allowed_extensions,
             tab_name,
             push_metadata,
             inherit_global_fields,
             colour,
             icon
        FROM resource_type
       WHERE ref = '{$ref}'
    ORDER BY `name`
", "schema");
if (count($restypedata)==0) {exit("Resource type not found.");} // Should arrive here unless someone has an old/incorrect URL.
$restypedata=$restypedata[0];

$inherit_global_fields_checked = ((bool) $restypedata['inherit_global_fields'] ? 'checked' : '');

include "../../include/header.php";

?>
<script src="<?php echo $baseurl_short ?>lib/chosen/chosen.jquery.min.js" type="text/javascript"></script>
<link rel="stylesheet" href="<?php echo $baseurl_short ?>lib/chosen/chosen.min.css">

<div class="BasicsBox">
<?php
$links_trail = array(
    array(
        'title' => $lang["systemsetup"],
        'href'  => $baseurl_short . "pages/admin/admin_home.php"
    ),
    array(
        'title' => $lang["resource_types_manage"],
        'href'  => $backurl
    ),
    array(
        'title' => htmlspecialchars(i18n_get_translated($restypedata["name"])),
        'help'  => "resourceadmin/resource-types"
    )
);

renderBreadcrumbs($links_trail);
?>
<?php if (isset($error_text)) { ?><div class="FormError"><?php echo $error_text?></div><?php } ?>
<?php if (isset($saved_text)) { ?><div class="PageInfoMessage"><?php echo $saved_text?></div><?php } ?>

<form method=post action="<?php echo $baseurl_short?>pages/admin/admin_resource_type_edit.php?ref=<?php echo urlencode($ref) ?>&backurl=<?php echo urlencode ($url) ?>">
<?php
generateFormToken("admin_resource_type_edit");

if($actions_required)
    {
    ?>
    <div class="PageInfoMessage">
    <?php
    if($confirm_delete)
        {
        echo str_replace("%%RESOURCECOUNT%%",count($affectedresources),$lang["resource_type_delete_confirmation"]) . "<br />";
        ?>
        <input type="hidden" name="prereq_action" value="move_affected_resources">
        <?php
        }
    else if($confirm_move_associated_rtf)
        {
        echo str_replace("%COUNT", count($affected_rtfs), $lang["resource_type_delete_assoc_rtf_confirm"]) . "<br>";
        ?>
        <input type="hidden" name="prereq_action" value="move_affected_rtfs">
        <?php
        }
    
    echo $lang["resource_type_delete_select_new"];
    ?>
    </div>
    <?php
    
    $destrestypes=$resource_types=sql_query ("
	select 
		ref,
		name
        from
		resource_type
	where
	    ref<>'$ref'
	order by name asc
	"
    );
    
    ?>
    <div class="Question">  
    <label for="targettype"><?php echo $lang["resourcetype"]; ?></label>    
    <div class="tickset">
      <div class="Inline"><select name="targettype" id="targettype" >
        <option value="" selected ><?php echo $lang["select"]; ?></option>
	<?php
    if($confirm_move_associated_rtf)
        {
        ?>
        <option value="0"><?php echo $lang["resourcetype-global_field"]; ?></option>
        <?php
        }
	  for($n=0;$n<count($destrestypes);$n++){
	?>
		<option value="<?php echo $destrestypes[$n]["ref"]; ?>"><?php echo htmlspecialchars(i18n_get_translated($destrestypes[$n]["name"])); ?></option>
	<?php
	  }
	?>
        </select>
      </div>
    </div>
	<div class="clearerleft"> </div>
    </div>
    <div class="QuestionSubmit">
        <label for="buttons"> </label>			
        <input name="cancel" type="submit" value="&nbsp;&nbsp;<?php echo $lang["cancel"]?>&nbsp;&nbsp;" />
        <input name="delete" type="submit" value="&nbsp;&nbsp;<?php echo $lang["action-delete"]?>&nbsp;&nbsp;" onClick="return confirm('<?php echo $lang["confirm-deletion"]?>');"/>
    </div>
    <?php
    exit();	
    }
else
    {
?> 
    
    <input type=hidden name=ref value="<?php echo urlencode($ref) ?>">
    
    <div class="Question"><label><?php echo $lang["property-reference"]?></label>
	<div class="Fixed"><?php echo  $restypedata["ref"] ?></div>
	<div class="clearerleft"> </div>
    </div>
    
    <div class="Question">
	<label><?php echo $lang["property-name"]?></label>
	<input name="name" type="text" class="stdwidth" value="<?php echo htmlspecialchars($restypedata["name"])?>" />
	<div class="clearerleft"> </div>
    </div>

    <div class="Question">
        <label><?php echo $lang["property-icon"]?></label>
        <select name="icon" class="stdwidth" id="icon-select">
            <option value="">
            <?php foreach ($font_awesome_icons as $icon_name => $icon_unicode) { ?>
                <option value="<?php echo htmlspecialchars($icon_name)?>" <?php if (trim($icon_name)==trim($restypedata["icon"])) {?>selected<?php } ?> data-icon="test">
                    <?php echo "&#x" . $icon_unicode . "&nbsp;" . "<span class='testclass'>" . htmlspecialchars(trim($icon_name)) . "</span>"; ?>
                </option>
            <?php } ?>
        </select>
        <!--<input name="icon" type="text" class="stdwidth" value="<?php echo htmlspecialchars($restypedata["icon"])?>" />-->
        <div class="clearerleft"> </div>
    </div>
    
    <div class="Question">
	<label><?php echo $lang["property-allowed_extensions"]?></label>
	<input name="allowed_extensions" type="text" class="stdwidth" value="<?php echo htmlspecialchars($restypedata["allowed_extensions"])?>" />
	
	<div class="FormHelp" style="padding:0;clear:left;" >
	    <div class="FormHelpInner"><?php echo $lang["information-allowed_extensions"] ?>
	    </div>
	</div>    
	<div class="clearerleft"> </div>    
    </div>
    
    <?php if (!$execution_lockout) { ?>
    <div class="Question">
	<label><?php echo $lang["property-override_config_options"] ?></label>
	<textarea name="config_options" class="stdwidth" rows=5 cols=50><?php echo htmlspecialchars($restypedata["config_options"])?></textarea>
	<div class="FormHelp" style="padding:0;clear:left;" >
	    <div class="FormHelpInner"><?php echo $lang["information-resource_type_config_override"] ?>
	    </div>
	</div>
	<div class="clearerleft"> </div>
    </div>
    <?php } ?>

    <div class="Question">
	<label><?php echo $lang["property-tab_name"]?></label>
	<input name="tab" type="text" class="stdwidth" value="<?php echo htmlspecialchars($restypedata["tab_name"])?>" />
	<div class="FormHelp" style="padding:0;clear:left;" >
	    <div class="FormHelpInner"><?php echo $lang["admin_resource_type_tab_info"] ?>
	    </div>
	</div>
	<div class="clearerleft"> </div>
    </div>

    <?php
    $MARKER_COLORS[-1] = $lang["select"];
    ksort($MARKER_COLORS);
    render_dropdown_question($lang['resource_type_marker_colour'],"colour",$MARKER_COLORS,$restypedata["colour"],'',array("input_class"=>"stdwidth"));
    ?>
    
        <div class="Question">
    <label><?php echo $lang["property-push_metadata"]?></label>
    <input name="push_metadata" type="checkbox" value="yes" <?php if ($restypedata["push_metadata"]==1) { echo "checked"; } ?> />
    <div class="FormHelp" style="padding:0;clear:left;" >
        <div class="FormHelpInner"><?php echo $lang["information-push_metadata"] ?>
        </div>
    </div>
    <div class="clearerleft"> </div>
    </div>

    <div class="Question">
        <label><?php echo $lang['property-inherit_global_fields']; ?></label>
        <input name="inherit_global_fields" type="checkbox" value="yes" <?php echo $inherit_global_fields_checked; ?> />
        <div class="FormHelp" style="padding:0;clear:left;" >
            <div class="FormHelpInner"><?php echo $lang['information-inherit_global_fields']; ?></div>
        </div>
        <div class="clearerleft"></div>
    </div>
    
    <div class="QuestionSubmit">
    <label for="buttons"> </label>			
    <input name="save" type="submit" value="&nbsp;&nbsp;<?php echo $lang["save"]?>&nbsp;&nbsp;" />
    <input name="delete" type="submit" value="&nbsp;&nbsp;<?php echo $lang["action-delete"]?>&nbsp;&nbsp;" onClick="return confirm('<?php echo $lang["confirm-deletion"]?>');"/>
    </div>
    <?php
    } // End of normal page (not confirm deletion)
    ?>

</form>
</div><!-- End of Basics Box -->

<script type="text/javascript">
    jQuery(document).ready(function(){
        jQuery('#icon-select').chosen({});
    });
</script>

<?php
include "../../include/footer.php";
?>
