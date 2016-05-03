/* Required libraries */
var SubscriptionManager = require("node_modules/pebble-subscription-manager/subscription_manager");
var Timeline = require('timeline');
var UI = require('ui');
var Wakeup = require('wakeup');
var ajax = require('ajax');
var Vibe = require('ui/vibe');

var minutes = 5;
Timeline.launch(function(e){
  if(e.action){
    var launchCode = e.launchCode.toString();
    console.log('launch code:'+launchCode);
    var channel = launchCode.substr(0,1);
    console.log('channel: '+channel);
    var start = parseInt(launchCode.substr(1)+"00000",10);
    console.log('start: '+start);
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
      var reminder = start-(minutes*60000);
      Wakeup.schedule(
        {
          time: reminder/1000,
          data: {title: title, channel: channelTitle, minutes: minutes}
        },
        function(ee){
          if(ee.failed){
            var failCard = new UI.Card({
              title: " ",
              subtitle:"GHTV Programs",
              icon: "images/gh-live-logo.png",
              body: "Unable to set alarm"
            });
            failCard.show();
          } else {
            var confirmCard = new UI.Card({
              title: " ",
              subtitle:"GHTV Programs",
              icon: "images/gh-live-logo.png",
              body: getReminderConfirmBody(title,channelTitle,start,minutes)
            });
            confirmCard.show();      
          }
        }
      );
      
    });
  ajax(
  {
    url: 'https://www.guitarhero.com/api/papi-client/ghl/v1/channelSchedules/en/all/',
    type: 'json'
  },
  function(data, status, request) {
    console.log("channel: "+channel);
    console.log("start: "+start);
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
    Wakeup.launch(function(e){
      if(e.wakeup){
        var alarmCard = new UI.Card({
              title: " ",
              subtitle:"GHTV Programs",
              icon: "images/gh-live-logo.png",
              body: getAlarmBody(e.data.title,e.data.channel,e.data.minutes)
            });
            alarmCard.show();
            Vibe.vibrate('long');
            setTimeout(function(){
              Vibe.vibrate('short');
              setTimeout(function(){
                Vibe.vibrate('long');
              },500);
            },500);
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

function getAlarmBody(title,channelTitle,minutes){
  return title+" will being on "+channelTitle+" in "+minutes+" minute"+(minutes==1?"":"s");
}