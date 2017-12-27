<?php

if (empty($_GET['file']))
  $file = 'kevin';
else
  $file = preg_replace("/[^a-z0-9]/", '', $_GET['file']);

if (!is_file("upload_files/$file.js"))
{
  print "<b>file reference provided in url is invalid</b>";
  $file = 'kevin';
}


?>
<head>
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-351485-5"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-351485-5');
</script>
<meta http-equiv="content-type"
  content="text/html; charset=UTF-8"> 
<meta name="google" content="notranslate" />
<link href="/style.css" media="screen" rel="stylesheet" type="text/css" />
<script src='<?php echo "upload_files/$file.js"; ?>'></script>
<script src='d3.v4.min.js'></script>
<style type='text/css'>

#info_panel_parent {
  position: absolute;
  top: 2px;
  /*right: 2px;*/
  height: 1000px;
}

#info_panel {
  background-color: #fff;
  opacity: 0.8;

  display: inline-block;
  vertical-align: top;
  border: 2px dashed red;

  width: 250px;

  padding: 20px;
  margin: 20px;

  font-size: 80%;

  position: -webkit-sticky;
  position: sticky;
  top: 10px;
  right: 10px;
}

#kanji_panel {
  text-align: center;
  border: 2px dashed #888;

  left: 0px;
  right: 0px;

  padding: 5px;
  margin: 10px;
}

#kanji_title {
  font-size: 1000%;
  text-align: center;
}

#kanji_details {
  padding-bottom: 10px;
}

.kanji {
  padding: 2px;
  color: rgb(240,240,240);
  cursor: crosshair;
}

.kanji.highlight {
  padding: 1px;
  border: 1px solid #888;
}

.chart {
  width: 100%;
  height: 100px;
  /*border: 1px solid red;*/
  border-left: 1px solid black;
  border-right: 1px solid black;
}

#model {
  opacity: 0.9;
  background-color: #fff;
  position: absolute;
  top: 10%;
  left: 10%;
  right: 10%;
  /*bottom: 10%;*/
  border: 1px dashed red;
  padding: 50px;
}

</style>
</head>
<body>
<div id='kanji_panel'></div>
<div id='info_panel_parent' style='display:none'>
  <div id='info_panel'>
    <div id='kanji_title'></div>
    <div id='kanji_details' style='font-size:90%'>#0 (unknown)</div>
    <div style='font-size:80%'><span id='first_review'></span></div>
    <div style='font-size:80%'><span id='review_count'></span></div>
    <div style='font-size:80%'><span id='next_review'></span></div>
    <svg id='kanji_timeline' class='chart'></svg>
    <div style='font-size:80%'><span id='time_start' style='float:left'></span><span id='time_end' style='float:right'></span></div>
    <div style='font-size:120%;padding-top:20px'><b>Instructions:</b></div>
    <div>Press space at any time to pause<br />Press +/- to change the speed</div>
    <!-- <div style='font-size:110%;padding-top:15px'>All reviews:</div> -->
    <!-- <svg id='kanji_counts' class='chart'></svg> -->
  </div>
</div>
<div id='model'>
  <?php
    if ($file == 'kevin')
      echo "Here are the Kanji that I've been learning since " . date("F Y", 1458833654) . "<br /><br />";
    else
      echo "Here are the Kanji that I've been learning.<br /><br />";
  ?>

  You know <i>brag brag</i> or whatever but what I find really interesting is the <a href='https://en.wikipedia.org/wiki/Spaced_repetition'>spaced repetition</a> memory process.<br />
  If you're not familiar, the idea is flash cards but they are only shown <i>just</i> as you're predicted to forget the content.<br />
  <br />
  This is a visualization of that process over time.<br />
  Freshly <span style='padding:2px; background-color:rgb(0,255,0)'>learnt</span> and freshly
  <span style='padding:2px; background-color:rgb(255,0,0)'>forgotten</span> kanji flash as time progresses in the simulation.<br />
  Each time I review a Kanji the text color turns <span style='color:#0f0'>green</span>.<br />
  The Kanji fade to <span style='color:#888'>dark grey</span> as time passes, symbolizing the memory fading.<br />
  <br />
  Each time a Kanji is reviewed, it will turn to <span style='color:#888'>dark grey</span> more slowly, which is the design of <a href=''https://en.wikipedia.org/wiki/Spaced_repetition'>SRS</a>.<br />
  If I can't remember a Kanji, reviews are intensified and the process restarts.<br />
  If you move the mouse around you can see more information about a particular Kanji.<br />
  Including a nice little graph of all the reviews for that particular Kanji.<br />
  <br />
  If you are also learning Kanji, you can try rendering this with your own reviews <a href='uploader.php'>here</a>.<br />
  <br />
  <div style='font-size:120%'>Press <b>space</b> to start</div>
</div>
</body>
<script>
var elems = new Array();
var highlight_elem = null;

var paused = true;
var speed = 6060;
var max_for_debug = 10000;

var flash_duration = 86400 * 3;

var timer = null;
// var time_start = 1458833654; // March 24, 2016 3:34:14 PM
// var time_end   = time_start + reviews[reviews.length-1][2];

var now = 0;
var prev_now = 0;
var global_review_index = 0;

var graph_width = 206; // hardcoded maybe thats bad
var x = null, y = null, line = null;
var kanji_counts = new Array();

function init()
{
  now = 0;//reviews[reviews.length-1][2] - 1000000;

  x = d3.scaleLinear().rangeRound([0, graph_width]).domain([0, time_end - time_start]);
  y = d3.scaleLinear().rangeRound([10, 90]).domain([0,1]);
  line = d3.line().x(function (d) { return x(d[0]) })
                  .y(function (d) { return y(d[1]) });

  global_review_index = 0;
  document.getElementById('time_start').innerHTML = '⇖' + get_date_from_rtime(0);
  document.getElementById('time_end').innerHTML = get_date_from_rtime(time_end - time_start) + '⇗';

  d3.select('#kanji_panel')
    .selectAll('.kanji')
    .data(kanji)
  .enter()
    .append('span')
    .attr('class', 'kanji')
    .text(function (d) { return d[0]; })
    .each(function (d, i) {
      this.kanji_id = i;
      kanji[i].kanji_id = i;
      kanji[i].character = kanji[i][0];
      kanji[i].keyword = kanji[i][1].replace('<div>', '').replace('</div>', '').replace(' ', '&nbsp;');
      kanji[i].elem = this;
      kanji[i].next_review_index = 0;
      kanji[i].bad_counter = 0;
      kanji[i].good_counter = 0;
      kanji[i].reviews = new Array();
      kanji[i].flashes = new Array();
    })
    .on('mouseover', function (d, i) {
      highlight_elem = this;
      d3.select(this).classed('highlight', true);
      update_d3_charts(this.kanji_id);
    })
    .on('mouseout', function (d, i) {
      d3.select(this).classed('highlight', false);
    });

  kanji_counts = new Array(new Array(),new Array(),new Array(),new Array()); // different 'ease's
  for (var i = 0 ; i <= graph_width ; i++)
  {
    kanji_counts[0][i] = 0;
    kanji_counts[1][i] = 0;
    kanji_counts[2][i] = 0;
    kanji_counts[3][i] = 0;
  }

  var max_reviews_in_one_pixel = 0;

  for (var i = 0 ; i < reviews.length ; i++)
  {
    reviews[i] = {
      kanji_id: reviews[i][0],
      ms:       reviews[i][1],
      rtime:    reviews[i][2],
      ease:     reviews[i][3],
      ivl:      reviews[i][4]
    }
    kanji[reviews[i].kanji_id].reviews.push(reviews[i]);

    kanji_counts[reviews[i].ease-1][x(reviews[i].rtime)]++;

    var tot = kanji_counts[0][x(reviews[i].rtime)] +
              kanji_counts[1][x(reviews[i].rtime)] +
              kanji_counts[2][x(reviews[i].rtime)] +
              kanji_counts[3][x(reviews[i].rtime)];

    if (tot > max_reviews_in_one_pixel)
      max_reviews_in_one_pixel = tot;
  }

  /*document.getElementById('kanji_counts').innerHTML = '';
  var kanji_counts_d3 = d3.select('#kanji_counts');

  for (var ease = 0 ; ease < 4 ; ease++)
  {
    var tt = kanji_counts_d3
      .selectAll('path.ease'+ease)
      .data(kanji_counts[ease]);
    tt.enter()
        .append('path')
        .attr("fill", "none")
        .attr("stroke", function (d, i) {
          if (ease == 0) return "#F00";
          if (ease == 1) return "#050";
          if (ease == 2) return "#0A0";
          if (ease == 3) return "#0F0";
        })
        .attr("d", function (d, i) {
          var tot = 0;
          for (var t = ease - 1; t >= 0; t--)
            tot += kanji_counts[t][i] / max_reviews_in_one_pixel;

          return line([[i/206*(time_end-time_start), 1 - tot], [i/206*(time_end-time_start), 1 - (tot + d / max_reviews_in_one_pixel)]]);
        });
  }*/
}

function get_date_from_rtime(rtime)
{
  var date = new Date((time_start + rtime) * 1000);
  date =  (date.getYear() + 1900) + "-" + 
          ((date.getMonth() + 1) < 10 ? "0" : "") + (date.getMonth() + 1) + "-" + 
          (date.getDate() < 10 ? "0" : "") + date.getDate();
  return date;
}

function update_d3_charts(kanji_id)
{
  var kan = kanji[kanji_id];

  if (document.getElementById('kanji_title').innerHTML != kan.character || prev_now == 0 || now-prev_now > 86400)
  {
    if (kan.reviews.length > 0)
    {
      var days = parseInt((kan.reviews[0].rtime - now) / 86400);
      document.getElementById('first_review').innerHTML = 'first review: ' + 
        get_date_from_rtime(kan.reviews[0].rtime) + ' (' + Math.abs(days) + ' day'+(Math.abs(days)==1?"":"s") + (days < 0 ? " ago" : "") + ')';

      document.getElementById('review_count').innerHTML = 'reviewed ' + kan.next_review_index + ' of ' + kan.reviews.length + ' time'+ (kan.next_review_index==1?"":"s")+' so far';

      var days = parseInt((kan.reviews[kan.next_review_index].rtime - now) / 86400);
      document.getElementById('next_review').innerHTML = 'next review: ' + 
        get_date_from_rtime(kan.reviews[kan.next_review_index].rtime) + ' (' + Math.abs(days) + ' day'+(Math.abs(days)==1?"":"s") + ')';

      if (kan.next_review_index == 0)
        document.getElementById('next_review').style.display = 'none';
      else
        document.getElementById('next_review').style.display = '';
    }
    else
    {
      document.getElementById('first_review').innerHTML = 'first review: never';
      document.getElementById('next_review').style.display = 'none';
    }

    // if (kan.next_review_index == 0)
    // {
    //   var days = parseInt((kan.reviews[kan.next_review_index].rtime - now) / 86400);
    //   // document.getElementById('first_review').innerHTML = 'first review: ' + 
    //    // get_date_from_rtime(kan.reviews[kan.next_review_index].rtime) + ' (' + days + ' day'+(days==1?"":"s")+')';
    //   document.getElementById('next_review').style.display = 'none';
    // }
    // else if (kan.reviews.length > 0 && kan.next_review_index < kan.reviews.length)
    // {
    //   var days = parseInt((kan.reviews[0].rtime - now) / 86400);
    //   // document.getElementById('first_review').innerHTML = "first review: " + 
    //   //   get_date_from_rtime(kan.reviews[0].rtime) + ' (' + days + ' day'+(days==1?"":"s")+')';
    //   var days = parseInt((kan.reviews[kan.next_review_index].rtime - now) / 86400);
    //   document.getElementById('next_review').style.display = '';
    //   document.getElementById('next_review').innerHTML = "next review: " + 
    //     get_date_from_rtime(kan.reviews[kan.next_review_index].rtime) + ' (' + days + ' day'+(days==1?"":"s")+')';
    // }
    // else if (kan.reviews.length > 0)
    // {
    //   var days = kan.reviews[kan.next_review_index - 1].ivl;
    //   if (days < 0) days = 0;

    //   document.getElementById('next_review').innerHTML = "next review: " + 
    //     get_date_from_rtime(kan.reviews[kan.next_review_index - 1].rtime + days*86400) + ' (' + days + ' day'+(days==1?"":"s")+')';
    // }
    // else
    // {
    //   document.getElementById('next_review').innerHTML = "<i>never reviewed</i>";
    // }
  }

  if (document.getElementById('kanji_title').innerHTML != kan.character)
  {
    document.getElementById('kanji_title').innerHTML = kan.character;
    document.getElementById('kanji_details').innerHTML = '#' + (kanji_id+1) + '&nbsp;(' + kan.keyword + ')';
  }

  // document.getElementById('kanji_timeline').innerHTML = '';
  var kanji_timeline = d3.select('#kanji_timeline');

  var tt = kanji_timeline
    .selectAll('path.review')
    .data(kan.reviews);

  tt.enter()
      .append('path')
      .attr("class", "review")
      .attr("fill", "none");
  tt.attr("stroke", function (d, i) {
      if (d.rtime > now) return "#CCC";
      else if (i == 0) return "#0F0";
      else if (d.ease == 1) return "#F00";
      else if (d.ease == 2) return "#5A5";
      else if (d.ease == 3) return "#5C5";
      else if (d.ease == 4) return "#0F0";
    })
    .attr("d", function (d, i) {
      if (d.ease == 1)
        return line([[d.rtime,0],[d.rtime,1]]);
      else
        return line([[d.rtime,0.1],[d.rtime,0.9]]);
    });
  tt.exit()
      .remove();

  var tt = kanji_timeline
    .selectAll('path.now')
    .data([now]);

  tt.enter()
      .append('path')
      .attr('class', 'now')
      .attr("fill", "none")
      .attr("z-index", "2000")
      .attr("stroke", "black")
      .attr("stroke-dasharray", "2,4");
  tt.attr("d", function (d, i) {
      return line([[d,0],[d,1]]);
    });
  tt.exit()
      .remove();

  var tt = kanji_timeline
    .selectAll('text.now')
    .data([now]);

  tt.enter()
      .append('text')
      .attr('class', 'now')
      .attr("font-size", "60%");
  tt.attr("text-anchor", function (d) {
      if (d < (time_end - time_start) * 0.25)
        return "start";
      else if (d > (time_end - time_start) * 0.75)
        return "end";
      else
        return "middle";
    })
    .attr("x", function (d) { return x(d) })
    .attr("y", function (d) { return y(1.1); })
    .text(function (d) {
      return get_date_from_rtime(d);
    });
  tt.exit()
     .remove();

  ////////////////////////////////////////////////

  // var kanji_counts_d3 = d3.select('#kanji_counts');

  // var tt = kanji_counts_d3
  //   .selectAll('path.now')
  //   .data([now]);

  // tt.enter()
  //     .append('path')
  //     .attr('class', 'now')
  //     .attr("fill", "none")
  //     .attr("z-index", "2000")
  //     .attr("stroke", "black")
  //     .attr("stroke-dasharray", "2,4")
  // tt.attr("d", function (d, i) {
  //     return line([[d,0],[d,1]]);
  //   });
  // tt.exit()
  //    .remove();


  // var tt = kanji_counts_d3
  //   .selectAll('text.now')
  //   .data([now]);

  // tt.enter()
  //     .append('text')
  //     .attr('class', 'now')
  //     .attr("font-size", "60%")
  // tt.attr("text-anchor", function (d) {
  //     if (d < (time_end - time_start) * 0.25)
  //       return "start";
  //     else if (d > (time_end - time_start) * 0.75)
  //       return "end";
  //     else
  //       return "middle";
  //   })
  //   .attr("x", function (d) { return x(d) })
  //   .attr("y", function (d) { return y(1.1); })
  //   .text(function (d) {
  //     return get_date_from_rtime(d);
  //   });
  // tt.exit()
  //    .remove();

}

function tick()
{
  if (!paused) //return;
    now += speed;

  if (highlight_elem)
  { 
    if (document.getElementById('kanji_title').style.color != highlight_elem.style.color)
      document.getElementById('kanji_title').style.color = highlight_elem.style.color;
    if (document.getElementById('kanji_title').style.backgroundColor != highlight_elem.style.backgroundColor)
      document.getElementById('kanji_title').style.backgroundColor = highlight_elem.style.backgroundColor;
    
    if (prev_now == 0 || now-prev_now > 86400)
    {
      update_d3_charts(highlight_elem.kanji_id);
      prev_now = now;
    }
  }

  for (var i = 0 ; i < kanji.length ; i++)
  {
    var kan = kanji[i];
    var elem = kan.elem;

    if (kan.flashes.length > 0)
    {
      var flash = kan.flashes[0];

      if (now > flash.start && now < flash.end)
      {
        var flash_progress = (now - flash.start) / (flash.end - flash.start);

        if (flash.type == 'new')
          elem.style.backgroundColor = "rgb("+parseInt(flash_progress*255)+",255,"+parseInt(flash_progress*255)+")";
        else if (flash.type == 'forgot')
          elem.style.backgroundColor = "rgb(255,"+parseInt(flash_progress*255)+","+parseInt(flash_progress*255)+")";
      }
      else if (now > flash.end)
      {
        elem.style.backgroundColor = "";
        kan.flashes.shift();
      }
    }

    if (kan.next_review_index > 0)
    {
      var review = kan.reviews[kan.next_review_index - 1];
      if (kan.ivl < 0) // in seconds - should be reviewed pretty well in 1 minute or 10 minutes, won't see this review visually.
        kan.progress = 0;
      else // ivl is days
        kan.progress = (now - review.rtime) / (review.ivl * 86400);

      elem.style.color = 'rgb(0,' + parseInt(255.0 - kan.progress * 200.0) + ', 0)';
    }
  }

  if (global_review_index < reviews.length)
  while (global_review_index < reviews.length && reviews[global_review_index].rtime < now)
  {
    var review = reviews[global_review_index];
    var kan = kanji[review.kanji_id];
    var elem = kan.elem;

    if (kan.next_review_index == 0) // first review
    {
      var start = kan.reviews[kan.next_review_index].rtime;
      if (kan.flashes.length > 0)
        start = kan.flashes[kan.flashes.length - 1].end;

      kan.flashes.push({
        type: 'new', 
        start: start, 
        end: start + flash_duration
      });

      kan.good_counter = kan.reviews[kan.next_review_index].rtime + flash_duration;
      d3.select(elem).classed('has_counter', true);
    }

    if (review.ease == 1 && kan.next_review_index > 0) // not first review, but rated it '1'
    {
      var start = kan.reviews[kan.next_review_index].rtime;
      if (kan.flashes.length > 0)
        start = kan.flashes[kan.flashes.length - 1].end;

      kan.flashes.push({
        type: 'forgot', 
        start: start, 
        end: start + flash_duration
      });
      
      kan.bad_counter = flash_duration;
      d3.select(elem).classed('has_counter', true);
    }

    kan.next_review_index++;
    global_review_index++;
  }

  if (global_review_index >= reviews.length)
  {
    paused = true;
  }
}

document.onmousemove = function (e) {
  if (e.clientX > window.innerWidth * 0.75 && document.getElementById('info_panel_parent').style.left == '')
  {
    document.getElementById('info_panel_parent').style.left = '2px';
    document.getElementById('info_panel_parent').style.right = '';
  }
  else if (e.clientX < window.innerWidth * 0.25 && document.getElementById('info_panel_parent').style.right == '')
  {
    document.getElementById('info_panel_parent').style.left = '';
    document.getElementById('info_panel_parent').style.right = '2px';
  }
}

document.onkeydown = function (e) {
  if (e.keyCode == 32) return false;
}

document.onkeyup = function (e) {
  if (e.keyCode == 32)
  {
    document.getElementById('model').style.display = 'none';
    document.getElementById('info_panel_parent').style.display = '';

    if (highlight_elem == null)
      highlight_elem = kanji[311].elem;
    paused = !paused;

    if (global_review_index >= reviews.length)
      paused = true;

    return false;
  }
  if (e.keyCode == 187) speed *= 1.2;
  if (e.keyCode == 189) speed *= 0.8;
}

document.addEventListener("DOMContentLoaded", function (e) {
  init();
  setInterval(tick, 10);
});


</script>
