doc_ready(function () {
    const btn = $j('.audit-log')
    if (btn.length === 0 ){
        $j.get('dropdown_menu.html', function(res){
            $j('.navbar-right').prepend(res);
        });
    }
});

function doc_ready(fn) {
    if (document.readyState === "complete" || document.readyState === "interactive") {
        setTimeout(fn, 2000);
    } else {
        document.addEventListener("DOMContentLoaded", fn);
    }
}    