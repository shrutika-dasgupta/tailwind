function sendMessage(sender, msg) {
  chrome.tabs.sendMessage(sender.id, msg);
  //console.log(sender, msg);
}

function handleMessage(message, sender) {
  sender = sender.tab;
  switch (message.command) {
  }
}

chrome.runtime.onMessage.addListener(handleMessage);

var Projects;
chrome.browserAction.onClicked.addListener(function (tab) {
  chrome.tabs.sendMessage(tab.id, {
    "command" : "tbButtonClicked"
  });
});
chrome.contextMenus.create({
  "type"      : "normal",
  "id"        : "soms-ct-image",
  "title"     : "Schedule with Tailwind Publisher",
  "contexts"  : ["image"],
  "onclick"   : function(data,tab){
    //Object {
    //editable: false, 
    //mediaType: "image", 
    //menuItemId: "soms-ct-menu", 
    //pageUrl: "http://fred.localhost/login.php", 
    //srcUrl: "http://fred.localhost/assets/img/header.png"} 
    /*
    POST:
    URL: http://alpha.analytics.tailwindapp.com/publisher/draft­posts
    Data: 
        JSON.stringify({
            items : [{
                site­url: Current Page URL,
                image­url: Image URL,
                description: Image Alt/Title Value,
            }]
        });
     */    
    //console.log(data);
    sendMessage(tab, {"command" : "imageClicked", "data" : data.srcUrl});
  }
});
chrome.contextMenus.create({
  "type"      : "normal",
  "id"        : "tw-ct-page",
  "title"     : "Schedule with Tailwind Publisher",
  "contexts"  : ["page"],
  "onclick"   : function(data,tab){
    //Object {
    //editable: false, 
    //mediaType: "image", 
    //menuItemId: "soms-ct-menu", 
    //pageUrl: "http://fred.localhost/login.php", 
    //srcUrl: "http://fred.localhost/assets/img/header.png"} 
    //selectionText: "Welcome to Team interaction section!"
    //console.log(data);
        console.log(data);
    sendMessage(tab, {"command" : "tbButtonClicked"});
  }
});
