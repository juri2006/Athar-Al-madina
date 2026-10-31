<?php
/*
 * الترجمة الآلية (Google Translate) لكل صفحات الموقع.
 * يُضمَّن هذا الملف مرة واحدة في آخر كل صفحة (قبل </body>):
 *     <?php include __DIR__ . "/translate_widget.php"; ?>
 * لتغيير اللغات المتاحة عدّل includedLanguages أدناه.
 */
$__pageLang = (isset($LANG) && is_string($LANG)) ? $LANG : "ar";
?>
<style>
#gt-box{position:fixed;top:16px;left:16px;z-index:99999;background:#fff;border:1px solid #cfdcd8;
border-radius:999px;padding:4px 8px;box-shadow:0 4px 14px rgba(0,0,0,.15);font-family:Arial,sans-serif;direction:ltr}
#gt-box .goog-te-gadget{font-size:0;color:transparent}
#gt-box .goog-te-gadget a{display:none}
#gt-box .goog-te-combo{font-size:13px;padding:5px;border-radius:8px;border:1px solid #cfdcd8;max-width:160px;cursor:pointer;color:#174c4c;background:#fff}
.goog-te-banner-frame{display:none!important}
body{top:0!important}
</style>
<div id="gt-box" class="notranslate" translate="no">
    <div id="google_translate_element"></div>
</div>
<script>
function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: "<?= htmlspecialchars($__pageLang) ?>",
        includedLanguages: "ar,en,ur,id,tr",   // اللغات المتاحة فقط (احذف ,tr لإزالة التركية)
        autoDisplay: false
    }, "google_translate_element");
}
// حذف شريط جوجل إذا ظهر، وإرجاع الصفحة لمكانها
(function(){
    function clean(){
        document.querySelectorAll("iframe.goog-te-banner-frame, iframe.VIpgJd-ZVi9od-ORHb-OEVmcd, .VIpgJd-ZVi9od-aZ2wEe-wOHMyf")
            .forEach(function(el){ el.style.display = "none"; });
        document.body.style.setProperty("top","0px","important");
        document.documentElement.style.setProperty("top","0px","important");
    }
    new MutationObserver(clean).observe(document.documentElement,{childList:true,subtree:true,attributes:true,attributeFilter:["style","class"]});
    document.addEventListener("DOMContentLoaded", clean);
})();
</script>
<script src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>