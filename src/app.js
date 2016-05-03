/* Required libraries */
var SubscriptionManager = require("node_modules/pebble-subscription-manager/subscription_manager");
var Timeline = require('timeline');
var UI = require('ui');
var Wakeup = require('wakeup');
var ajax = require('ajax');

var minutes = 5;
Timeline.launch(function(e){
  if(e.action){
    var channel = parseInt(e.launchCode.toString().substr(0,1),10);
    var start = parseInt(e.launchCode.toString().substr(1)+"0000",10);
    var title = "";
    var channelTitle = "";
    var card = new UI.Card({
      title: " ",
      subtitle:"GHTV Programs",
      icon: "images/gh-live-logo.png",
      body: getReminderBody(minutes)
    });
    card.on('click','up',function(){
      if(minutes > 0){
        minutes--;
        if(minutes < 0){
          minutes = 0;
        }
        card.body(getReminderBody(minutes));
      }
    });
    card.on('click','down',function(){
      if(minutes < 30){
        minutes++;
        if(minutes > 30){
          minutes = 30;
        }
        card.body(getReminderBody(minutes));
      }
    });
    card.on('click','select',function(){
      var confirmCard = new UI.Card({
        title: " ",
        subtitle:"GHTV Programs",
        icon: "images/gh-live-logo.png",
        body: getReminderConfirmBody(title,channelTitle,start,minutes)
      });
      confirmCard.show();
    });
  ajax(
  {
    url: 'https://www.guitarhero.com/api/papi-client/ghl/v1/channelSchedules/en/all/',
    type: 'json'
  },
  function(data, status, request) {
    channelTitle = data.data[channel].title;
    var programs = data.data[channel].programmes;
    for(var i = 0; i< programs.length;i++){
      if(programs[i].startTime == start) {
        title = programs[i].title;
        break;
      }
    }
    card.show();
  },
  function(error, status, request) {
    console.log('The ajax request failed: ' + error);
  }
  );
    
    
  } else {
    var sm = new SubscriptionManager({title: "  ",subtitle:"GHTV Programs", "icon":"images/gh-live-logo.png"});



    //topics
    var topics = [
      {id: "anthems", title:"Anthems", subscribed: false, icon:""},
      {id: "blockbusters", title:"Blockbusters", subscribed: false, icon:""},
      {id: "classics", title:"Classics", subscribed: false, icon:""},
      {id: "headliners", title:"Headliners", subscribed: false, icon:""},
      {id: "hits", title:"Hits", subscribed: false, icon:""},
      {id: "indie", title:"Indie", subscribed: false, icon:""},
      {id: "jams", title:"Jams", subscribed: false, icon:""},
      {id: "knockouts", title:"Knockouts", subscribed: false, icon:""},
      {id: "metal", title:"Metal", subscribed: false, icon:""},
      {id: "picks", title:"Picks", subscribed: false, icon:""},
      {id: "pop", title:"Pop", subscribed: false, icon:""},
      {id: "riffs", title:"Riffs", subscribed: false, icon:""},
      {id: "rock", title:"Rock", subscribed: false, icon:""},
      {id: "smashes", title:"Smashes", subscribed: false, icon:""},
      {id: "other", title:"Other", subscribed: false, icon:""}
    ];
    
    sm.addTopics(topics);
    sm.start();
    
  }
});

function getReminderBody(i){
  return "Press 'Select' to set a reminder for "+i+" minute"+(i==1?"":"s")+" before start.";
}


function getReminderConfirmBody(title,channelTitle,start, minutes){
  var reminder = start-(minutes*60000);
  var d = new Date(reminder);
  return "A reminder has been set for "+title+" on "+channelTitle+" at "+d.getHours()+":"+d.getMinutes();
}