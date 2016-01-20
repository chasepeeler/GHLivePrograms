/* Required libraries */
var Subscription = require("subscriptions");


var s = new Subscription({title: "GHTV",subtitle:"Programming"});



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

s.addTopics(topics);
s.start();
