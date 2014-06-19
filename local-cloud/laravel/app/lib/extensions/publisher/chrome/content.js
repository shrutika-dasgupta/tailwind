//var logo = "http://localhost/Test/icon-19.png";
var logo = "http://www.tailwindapp.com/img/tailwind-16.png";
var submitURL = "http://analytics.tailwindapp.com/publisher/draft-posts?source=extension&browser=chrome";
function sendMessage(msg) {
  chrome.runtime.sendMessage(msg);
};
function handleMessage(message) {
  console.log(message);
  switch (message.command) {
    case "rec_ServerResponse":
      console.log(message.data);
      create_c6_sidebar(message.data)
      break;
    case "tbButtonClicked":
      tailWind.executeJS();
      break;
    case "imageClicked":
      var alt = tailWind.getImageDesc(message.data);
      var data = JSON.stringify({
        items : [{
          "site-url": location.href,
          "image-url": message.data,
          "description": alt,
        }]
      });
      tailWind.postToUrl(submitURL, data);
      //sendMessage({"command" : "postContextImage", "data" : data});
      break;
    case "formatCopiedText":
      checkSelection();
      toggleFrame();
      break;
  }
};
chrome.runtime.onMessage.addListener(handleMessage);

var tailWind = {
  executeJS : function(){
    var date = new Date();var timestamp = date.getUTCMonth() + '-' + 
                date.getUTCDate() + '-' + date.getUTCFullYear();
    var s = document.createElement('script');
    s.src = '//analytics.tailwindapp.com/js/publisher/bookmarklet.js?ts=' + timestamp;
    document.body.appendChild(s);
  },
  getImageDesc : function (src){
    var all = document.querySelectorAll("img");
    for(i=0; i< all.length; i++){
      if(all[i].src === src){
        return all[i].alt;
      }
    }
  },
  submitCreatePin : function(){
    var s = location.search.slice(1);
    var params = s.split("&");
    var o = {}, tmp;
    for(i=0; i<params.length; i++){
      tmp = params[i].split("=");
      o[tmp[0]] = decodeURIComponent(tmp[1]);
    }
    if(o.media !== undefined && o.url !== undefined && o.description !== undefined){
      var data = JSON.stringify({
          items : [{
            "site-url": o.url,
            "image-url": o.media,
            "description": o.description
          }]
        });
      tailWind.postToUrl(submitURL, data, "POST", "_blank");
    }
    
  },
  submitPinDetails : function(){
    var pinId = location.href.match(/\d+/ig)[0];
    var siteUrl   = document.location.href;
    var imageUrl  = document.querySelector(".closeupContainer .imageContainer img").src;
    var imageDesc = document.querySelector(".closeupContainer .imageContainer img").alt;
    var data = JSON.stringify({
      items : [{
        "site-url"      : siteUrl,
        "image-url"     : imageUrl,
        "description"   : imageDesc,
        "parent-pin-id" : pinId,
      }]
    });
    tailWind.postToUrl(submitURL, data);
  },
  submitPinFeed : function(){
    console.log("Pin feed button clicked");
    event.preventDefault();
    var data = JSON.stringify({
      items : [{
        "site-url"      : document.location.href,
        "image-url"     : this.dataset["imgUrl"],
        "description"   : this.dataset["imgAlt"],
        "parent-pin-id" : this.dataset["pinId"]
      }]
    });
    tailWind.postToUrl(submitURL, data);
  },
  postToUrl : function (path, params, method, target) {
    method = method || "POST";
    target = target || "_blank";

    var form = document.createElement("form");
    form.setAttribute("method", method);
    form.setAttribute("action", path);
    form.setAttribute("target", target);

    var formField = document.createElement("input");
    formField.setAttribute("type", "hidden");
    formField.setAttribute("name", "data");
    formField.setAttribute("value", params);
    form.appendChild(formField);

    document.body.appendChild(form);
    form.submit();
  } 
}

if(document.location.href.indexOf("www.pinterest.com/pin/create/") != -1){
/*http://www.pinterest.com/pin/create/bookmarklet/?media=https%3A%2F%2Fs1.yimg.com%2Fdh%2Fap%2Fdefault%2F130909%2Fy_200_a.png&url=https%3A%2F%2Fwww.yahoo.com%2F&description=Yahoo
 s=location.search
"?media=https%3A%2F%2Fs1.yimg.com%2Fdh%2Fap%2Fdefault%2F130909%2Fy_200_a.png&url=https%3A%2F%2Fwww.yahoo.com%2F&description=Yahoo"
s.slice(1)
"media=https%3A%2F%2Fs1.yimg.com%2Fdh%2Fap%2Fdefault%2F130909%2Fy_200_a.png&url=https%3A%2F%2Fwww.yahoo.com%2F&description=Yahoo"
s=s.slice(1)
"media=https%3A%2F%2Fs1.yimg.com%2Fdh%2Fap%2Fdefault%2F130909%2Fy_200_a.png&url=https%3A%2F%2Fwww.yahoo.com%2F&description=Yahoo"
s.split("&")
["media=https%3A%2F%2Fs1.yimg.com%2Fdh%2Fap%2Fdefault%2F130909%2Fy_200_a.png", "url=https%3A%2F%2Fwww.yahoo.com%2F", "description=Yahoo"] 

*/ 
  var b = document.createElement("button");
  b.setAttribute("id", "tw_btn_createPin");
  b.setAttribute("style", "padding:8px;");
  b.setAttribute("class", "rounded Button btn");
  
  var em = document.createElement("em");
  em.setAttribute("style","background-image: url(" + logo + "); width: 16px;height: 16px;margin-top: -4px;");
  b.appendChild(em);
  
  var span = document.createElement("span");
  span.textContent = "Schedule";
  b.appendChild(span);
  
  var p = document.querySelector(".formFooterButtons");
  p.appendChild(b);
  
  document.querySelector("#tw_btn_createPin").onclick = function(event){
    console.log("button TW clicked");
    event.preventDefault();
    tailWind.submitCreatePin();
    window.close();
  }
}


function addGlobalStyle(doc, css) {
  var head = doc.getElementsByTagName('head')[0]; // find head element, which should exist
  if (!head) {
    return;
  } // defective HTML document
  var style = doc.createElement('style'); // create <style> element
  style.type = 'text/css';
  if (style.styleSheet) { // for some cross-browser problem
    style.styleSheet.cssText = css; // attach CSS text to style elt
  } else {
    style.appendChild(document.createTextNode(css)); // attach CSS text to style elt
  }
  head.appendChild(style); // attach style element to head
  console.log("added style..");
}
var css,html;
var loc = location.href;
function attachPinDetail(){
// LikeButton rounded PinLikeButton Button Module hasText medium like pinActionBarButton btn
// rounded Button medium pinActionBarButton btn tw_schedule_btn  
  p = document.querySelector(".repinLike");
  if(p){
    if(p.classList.contains("tw_scheduled")) return;
      d = document.createElement("button");
      d.id = "tw_btn_pinDetails";
      d.setAttribute("class","rounded Button medium pinActionBarButton btn tw_schedule_btn  ");
      d.setAttribute("style", "padding:8px; margin-right:6px");
      d.innerHTML = "<em style = 'background-image: url("+ logo + "); width: 16px; height: 16px; margin-top: -4px;margin-right:3px'></em><span>Schedule</span>";
      p.insertBefore(d, p.querySelector(".LikeButton"));
      d.onclick = tailWind.submitPinDetails;
      p.classList.add("tw_scheduled");
  }
}
function attachPinfeed(){
  var all = document.querySelectorAll(".pinImageActionButtonWrapper");
  var cur, d, pinId, t, b, imgurl, imgdesc;
  console.log("Pin feed grid..", all.length);
  for (var i = 0; i < all.length; i++) {
    cur = all[i];
    if(cur.classList.contains("tw_scheduled")) continue;
    t = cur.querySelector(".pinHolder a");
    imgurl = cur.querySelector(".pinHolder a img").src;
    imgdesc= cur.querySelector(".pinHolder a img").alt;
    
    pinId = t.href.match(/\d+/ig)[0];
    d = document.createElement("div");
    d.setAttribute("class", "tw-btn-wrap");
    d.setAttribute("style","position: absolute; left: 8px; bottom: 8px; z-index: 105");
    b = document.createElement("button");
    b.setAttribute("class", "rounded Button btn tw_schedule_btn");
    b.setAttribute("style", "padding:8px;");
    b.dataset["pinId"] = pinId;
    b.dataset["imgUrl"] = imgurl;
    b.dataset["imgAlt"] = imgdesc;
    b.innerHTML = "<em style='background-image: url("+logo+"); width: 16px;height: 16px;margin-top: -4px; margin-right: 3px'></em><span>Schedule</span>";
    b.onclick = tailWind.submitPinFeed;
    d.appendChild(b);
    cur.appendChild(d);
    all[i].classList.add("tw_scheduled");
  }
}
function attachScheduleButtons(){
  attachPinDetail();
  attachPinfeed();
}
if(document.location.href.indexOf("www.pinterest.com") != -1){
  css = ".pinImageActionButtonWrapper{position: relative}.tw-btn-wrap{opacity:0}\
         .pinImageActionButtonWrapper:hover .tw-btn-wrap{opacity:1}\
         .tw_schedule_btn{padding:8px;}";
  addGlobalStyle(document, css);
  setInterval(attachScheduleButtons, 1500);
}

































