document.addEventListener("DOMContentLoaded",function(){
var d=document.createElement("div");
d.id="suite-nav";
d.style.cssText="position:fixed;bottom:20px;right:20px;z-index:9999;font-family:monospace;font-size:12px;";
var links=[
["http://localhost:8082","\u26A1 Dashboard"],
["http://localhost:8082/logs","\uD83D\uDCCB Log Viewer"],
["http://localhost:8081","\u26A1 Adminer"],
["http://localhost:8025","\uD83D\uDCE7 Mailpit"],
["http://localhost:9090","\uD83D\uDC33 Portainer"]
];
var menu="";
links.forEach(function(l,i){
menu+='<div style="padding:8px 12px;color:#569cd6;cursor:pointer;border-bottom:'+(i<links.length-1?'1px solid #222':'none')+';" onmouseover="this.style.background=\'1a1a1a\'" onmouseout="this.style.background=\'transparent\'" data-href="'+l[0]+'">'+l[1]+'</div>';
});
d.innerHTML='<div id="suite-nav-menu" style="display:none;background:#111;border:1px solid #333;border-radius:6px;margin-bottom:8px;overflow:hidden;min-width:180px;box-shadow:0 4px 12px rgba(0,0,0,.5);">'+menu+'</div><button id="suite-nav-btn" style="background:#1a1a1a;border:1px solid #333;color:#4ec9b0;width:40px;height:40px;border-radius:50%;cursor:pointer;font-size:16px;">\u2630</button>';
document.body.appendChild(d);
document.getElementById("suite-nav-btn").addEventListener("click",function(e){
e.stopPropagation();e.preventDefault();
var m=document.getElementById("suite-nav-menu");
m.style.display=m.style.display==="none"?"block":"none";
});
document.getElementById("suite-nav-menu").addEventListener("click",function(e){
var t=e.target.closest("[data-href]");
if(t)window.location.href=t.dataset.href;
});
document.addEventListener("click",function(){document.getElementById("suite-nav-menu").style.display="none";});
});