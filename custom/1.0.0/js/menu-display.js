var menuDisplayVMove = 1;
var menuDisplayHMove = 1;

$(document).ready(function(){

  $("#toolbar_ico li").mousedown(function(){
    menuDisplayPressDown(this);
  });
  
  $("#toolbar_ico li").mouseup(function(){
    menuDisplayPressUp(this);
  });

});
  
function menuDisplayPressDown(el){
    var mt = $(el).css("margin-top");
    mt = (parseInt(mt.substr(0,mt.length-2)) + menuDisplayVMove) + "px";
    var mb = $(el).css("margin-bottom");
    mb = (parseInt(mb.substr(0,mb.length-2)) - menuDisplayVMove) + "px";
    var ml = $(el).css("margin-left");
    ml = (parseInt(ml.substr(0,ml.length-2)) + menuDisplayHMove) + "px";
    var mr = $(el).css("margin-right");
    mr = (parseInt(mr.substr(0,mr.length-2)) - menuDisplayHMove) + "px";
    $(el).css({marginTop: mt, marginBottom: mb, marginLeft: ml, marginRight: mr});
}
  
function menuDisplayPressUp(el){
    var mt = $(el).css("margin-top");
    mt = (parseInt(mt.substr(0,mt.length-2)) - menuDisplayVMove) + "px";
    var mb = $(el).css("margin-bottom");
    mb = (parseInt(mb.substr(0,mb.length-2)) + menuDisplayVMove) + "px";
    var ml = $(el).css("margin-left");
    ml = (parseInt(ml.substr(0,ml.length-2)) - menuDisplayHMove) + "px";
    var mr = $(el).css("margin-right");
    mr = (parseInt(mr.substr(0,mr.length-2)) + menuDisplayHMove) + "px";
    $(el).css({marginTop: mt, marginBottom: mb, marginLeft: ml, marginRight: mr});
}