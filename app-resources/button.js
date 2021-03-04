    doc_ready(function () {
            const btn = $j('.audit-log')
            if (btn.length === 0 ){
                const span = $j('<span />',{
                    class:"text-warning",
                    text:"Audit Log"
                }).prepend('<i class="glyphicon glyphicon-tasks"></i>')
                const a = $j('<a />',{
                    class:"navbar-brand audit-log",
                    href:"auditLog.php"
                }).append(span)
        
                $j('.navbar-right').prepend(a);
            }
    });

    function doc_ready(fn) {
        if (document.readyState === "complete" || document.readyState === "interactive") {
            setTimeout(fn, 1);
        } else {
            document.addEventListener("DOMContentLoaded", fn);
        }
    }    