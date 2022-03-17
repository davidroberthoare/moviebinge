<?PHP
    session_start();
    $loc = isset($_COOKIE['loc']) ? $_COOKIE['loc'] : '';
    $lat = isset($_COOKIE['lat']) ? $_COOKIE['lat'] : '';
    $lng = isset($_COOKIE['lng']) ? $_COOKIE['lng'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />

  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />

  <title>MovieBinge</title>
  <meta name="description" content="" />
  <meta name="author" content="davidhoare" />

  <meta name="viewport" content="width=device-width, initial-scale=1.0" />

  	<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
	<link rel="apple-touch-icon" href="/apple-touch-icon.png" />
	<link rel="apple-touch-icon" sizes="57x57" href="/apple-touch-icon-57x57.png" />
	<link rel="apple-touch-icon" sizes="72x72" href="/apple-touch-icon-72x72.png" />
	<link rel="apple-touch-icon" sizes="76x76" href="/apple-touch-icon-76x76.png" />
	<link rel="apple-touch-icon" sizes="114x114" href="/apple-touch-icon-114x114.png" />
	<link rel="apple-touch-icon" sizes="120x120" href="/apple-touch-icon-120x120.png" />
	<link rel="apple-touch-icon" sizes="144x144" href="/apple-touch-icon-144x144.png" />
	<link rel="apple-touch-icon" sizes="152x152" href="/apple-touch-icon-152x152.png" />


	<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jquerymobile/1.4.5/jquery.mobile.min.css">
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquerymobile/1.4.5/jquery.mobile.min.js"></script>
  <script src="moment.min.js"></script>
  <script src="cookies.js"></script>
  <?PHP include('config.php');?>
	<script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?sensor=false&key=<?PHP echo $google_api_key;?>"></script>
	<link rel="stylesheet" href="style.css">

  <style>
  	html{
		/*text-align:center;	*/
	}
  </style>


<script>
	//main functionality

	var all_data;
	var theatres = [];
  var movies = {};

  // var selected_theatres = [];
	// var movie_list = [];	//list of movie names only, for display
	var showtimes = [];	//will hold all showtimes of selected movies at selected theatres, sorted chronologically
	var d = new Date();
	var today = d.toDateString();



  var binges = [];
  // var start_timer = moment();
  // var next_movie = true;
  // var selected_movie_list = [];
  // var selected_showtimes = [];

  var time_pad = 5; //min minutes between movies



	$( document ).ready(function() {

      $("#startSearch").click(function(){
        if($("#loc").val() === ""){
  				console.log("no location info");
  				toast("Please enter a Location");
  				return(false);
  			}else{
          if($("#lat").val() !== "" && $("#lng").val() !== ""){
            console.log("got lat/lng, so doing search");
  			  	getTimeData();
          }else{
            console.log("no lat/lng, so searching that first...");
  			  	getLatLngForAddress(getTimeData);
          }
        }
			});



     $("#loc").on('input',function(){
       $("#lat, #lng").val("");
     });

     $("#updateLocation").click(function(){
       console.log("updateLocation clicked");
       getLocation();
     });

     function getLocation(){
       if(navigator.geolocation) {
  		 		console.log("getting location from browser & google...");
  				 $("#loc").val("please wait...");
  				 $("#loc").prop("placeholder","getting location...");
  				 navigator.geolocation.getCurrentPosition(function (pos) {
  					 var geocoder = new google.maps.Geocoder();
  					 var lat = pos.coords.latitude;
  					 var lng = pos.coords.longitude;
  					 var latlng = new google.maps.LatLng(lat, lng);

  					 //reverse geocode the coordinates, returning location information.
  					 geocoder.geocode({ 'latLng': latlng }, function (results, status) {
  							 if(status == "OK"){
  								 var result = results[0];
  								 console.log("GEO INFO: " + status);
  								 console.log(result);
  								 $("#loc").val(result.formatted_address);
  								 $("#lat").val(result.geometry.location.lat());
  								 $("#lng").val(result.geometry.location.lng());
  							 }

  					 });
  			 }, function() {
  					 handleNoGeolocation(true);
  				 });

  		 }else {
  				 // Browser doesn't support Geolocation
  				 handleNoGeolocation(false);
  		 }
     }


     function handleNoGeolocation(errorFlag) {
 			if (errorFlag) {
 				toast("We can't detect your location.<br>Please enter it manually.");
 			} else {
 				toast("Your browser doesn't allow geolocation.<br>Please enter it manually.");
 			}
 			$("#loc").prop("placeholder","enter your location...");
 		}



 		function getLatLngForAddress(callback){
 			console.log("getteing LATLNG for address:");
 			var geocoder = new google.maps.Geocoder();
 			var address = $("#loc").val();

 			geocoder.geocode( { 'address': address}, function(results, status) {

 				if (status == google.maps.GeocoderStatus.OK) {
 					var latitude = results[0].geometry.location.lat();
 					var longitude = results[0].geometry.location.lng();
 					console.log(results[0]);
 					$("#lat").val(results[0].geometry.location.lat());
 					$("#lng").val(results[0].geometry.location.lng());

          callback();
 				}
 			});
 		}


     $( "#myPopupDiv" ).popup();// initialize it

     $("#movie_list").on("click", ".movie_details", function(){
       console.log("clicked!");
       event.stopImmediatePropagation();
       popupDetails($(this).attr("data-movie-id"));
     });

     $("#theatres").on("click", ".theatre_details", function(){
       console.log("clicked!");
       event.stopImmediatePropagation();
       popupTheatreDetails($(this).attr("data-theatre"));
     });




	});
  //!!!!!!!!!!! END DOCUMENT READY



		var toast=function(msg){
  		$("<div class='ui-loader ui-overlay-shadow ui-body-e ui-corner-all'><h3>"+msg+"</h3></div>")
  		.css({ display: "block",
  			opacity: 0.90,
  			position: "fixed",
  			padding: "7px",
  			"text-align": "center",
  			width: "270px",
  			"background-color":"#fff",
  			left: ($(window).width() - 284)/2,
  			top: $(window).height()/2 })
  		.appendTo( $.mobile.pageContainer ).delay( 1500 )
  		.fadeOut( 400, function(){
  			$(this).remove();
  		});
  	};



    //get initial timedata from source, via json request
		var getTimeData = function(){

			$.mobile.loading( "show", {
								text: "loading showtimes for that area",
								textVisible: true
				});

      setCookie("loc", $("#loc").val(), 360); //set the cookies for future visits
      setCookie("lat", $("#lat").val(), 360); //set the cookies for future visits
      setCookie("lng", $("#lng").val(), 360); //set the cookies for future visits

			var gUrl = "gettimes.php";
			$.getJSON( gUrl, {
				// loc: ($("#loc").val() != "")  ? $("#loc").val() : "toronto, ON",
				lat: ($("#lat").val() !== "") ? $("#lat").val() : "1",
				lng: ($("#lng").val() !== "") ? $("#lng").val() : "3",
				date:($("#date").val() !== "") ? $("#date").val() : moment().format("YYYY-MM-DD")
				// ,refresh:"true"
			} )
			.done(function( json ) {
				console.log( "JSON Data: " );
				console.log( json);
				if(json.error == false){

					//set the main data
					all_data = json.data;

          //hunt through the data getting the list of theatres
          theatres = [];
          var theatrelist = [];
					$.each( all_data, function( i, movie ) {
  					$.each( movie.showtimes, function( i, showtime ) {
              theatrelist.push(showtime.theatre);
  					});
					});
          theatrelist = theatrelist.makeUnique(true);  // uniquify and sort list
          $.each( theatrelist, function( i, theatre ) {
            theatres.push({name:theatre, selected:false});
          });
          console.log(theatres);
          populateTheatreList();

					//done, so navigate to next step
					docCookies.setItem("loc", $("#loc").val());	//set the cookie for next time...
					$.mobile.loading( "hide");
					$.mobile.navigate("#theatres"); //navigate to page
          $('ul').listview().listview("refresh");

				}else{
					$.mobile.loading( "hide");
					alert("error fetching data: " + json.error);
				}
			})
			.fail(function( jqxhr, textStatus, error ) {
				var err = textStatus + ", " + error;
				console.log( "Request Failed: " + err );
				$("#theatres").innerHTML = "<li>Error fetching theatres</li>";
				$.mobile.loading( "hide");
			});

		}

    function populateTheatreList(){
      $("#theatre_list").empty(); //clear the list

      //load list of theatres onto page
      $.each( theatres, function( i, theatre ) {
        console.log(theatre);
        var theatre_row = '<label><input type="checkbox" value="'+i+'" data-name="'+theatre.name+'">'+theatre.name;
        theatre_row += '<span class="theatre_details" data-theatre="'+theatre.name+ '"> - SHOWTIMES</span>';
        theatre_row += '</label>';
        $( theatre_row).appendTo( "#theatre_list").trigger( "create" );
      });
    }

    //using chosen theatres, show movies
		function findMoviesInTheatres(){
			selected_theatres = [];
			console.log("setting theatres");
			$('#theatre_list input:checkbox:checked').map(function() {
					theatres[this.value].selected = true;
          selected_theatres.push( $(this).attr("data-name") );
			})
      console.log(selected_theatres);

      if(selected_theatres.length == 0){
				console.log("no theatres");
				toast("Please select a theatre");
				return(false);
			}



      movies = {};  //blank the list of movies before adding again
      $.each( all_data, function( i, movie ) {
        $.each( movie.showtimes, function( i, showtime ) {
          // console.log("testing: " + showtime.theatre);
          if(selected_theatres.indexOf(showtime.theatre) > -1){  //add the film data to the list of movies showing at the selected    theatres
            if(!(movie.id in movies)){
              //add it to the list of movies if it's not already there.
              var id = movie.id;
              movie.selected = false;
              movies[id] = movie;
            }
          }
        });
      });
      console.log(movies);

      populateMovieList();

      $.mobile.navigate("#movies"); //navigate to page
      $('ul').listview().listview("refresh");
      return;

		}

    function populateMovieList(){
      var movies_sorted = [];
      $.each(movies, function(i, m){
            movies_sorted.push(m)
      });
      movies_sorted.sort(function(a, b) {
        return a.title > b.title ? 1 : -1;
      });
      // console.log(movies_sorted);

      $("#movie_list").empty();
			$.each(movies_sorted, function(i, m){
				var movie_row = '<label>';
        movie_row += '<input type="checkbox" value="'+m.id+'">';
        movie_row += m.title;
        var details = [];
        if(m.ratings !== null) details.push(m.ratings);
        if(m.qualityRating !== null) details.push(m.qualityRating);
        var details_string = (details.length > 0) ? ("(" + details.join(", ") + ")") : "";
        movie_row += '<span class="movie_details" data-movie-id="'+m.id+ '"> '+ details_string+' - Details</span>';
        movie_row += '</label>';
				$( movie_row).appendTo( "#movie_list").trigger( "create" );
			});

    }

    // utility function for star ratings
    $.fn.stars = function() {
        return this.each(function(i,e){$(e).html($('<span/>').width($(e).text()*16));});
    };



    function popupDetails(id){
      var msg = "this is the movie ID: " + id;
      var m = movies[id];
       msg = '<h3>'+m.title+'</h3>';
       msg += '<p>Summary: '+m.shortDescription+'</p>';
       msg += '<p>Details: '+m.longDescription+'</p>';
       msg += '<p>Director: '+m.directors.join(", ")+'</p>';
       msg += '<p>Cast: '+m.topCast.join(", ")+'</p>';
       msg += '<p>Length: '+moment.duration(m.runTime).as('minutes')+' min</p>';
       if(m.ratings !== null) msg += '<p>Rating: '+m.ratings+'</p>';
       if(m.advisories !== null) msg += '<p>Advisories: '+m.advisories.join(", ")+'</p>';
       if(m.qualityRating !== null) msg += '<p>Quality: <span class="stars">'+m.qualityRating+'</span></p>';
       if(m.officialUrl !== null) msg += '<p><a href="'+m.officialUrl+'" target="_blank">Official Link</a></p>';
      $("#popup_content").html(msg);
      $('.stars').stars();
      $( "#myPopupDiv" ).popup( "open" )
    }


    function popupTheatreDetails(theatre){
      var theatre_shows = [];
      $.each(all_data, function(i, m){
        var movie_data = [];
        $.each(m.showtimes, function(j, st){
          if(st.theatre == theatre){
            movie_data.push(moment(st.dateTime).format("h:mm a"));
          }
        });
        if(movie_data.length > 0){
          theatre_shows.push({
            title: m.title,
            times : movie_data
          });
        }
      });
      theatre_shows.sort(function(a, b) {
        return a.title > b.title ? 1 : -1;
      });
      console.log(theatre_shows);

      var msg = "this is the theatre ID: " + theatre;
      msg = '<h3>SHOWTIMES for: '+theatre+'</h3>';
      msg += '<ul class="showtime_list">';
      $.each(theatre_shows, function(i, show){
        msg += '<li><b>' + show.title + '</b>';
        msg += '<br>' + show.times.join(", ");
        msg += '</li>';
      });
      msg += '</ul>';


      $("#popup_content").html(msg);
      $( "#myPopupDiv" ).popup( "open" )
    }


    function getBinges(){
			binges = [];
  		showtimes = [];

			// console.log("selecting movies");
  		var selected_movie_list = $('#movie_list input:checkbox:checked').map(function() {
  				return this.value;
  		}).get();

  		if(selected_movie_list.length == 0){
  			console.log("no movies");
  			toast("Please select some movies");
  			return(false);
  		}


			//build list of showtimes for selected movies
			$.each(selected_movie_list, function(i, m){
        // console.log(m);
        // console.log(movies[m]);
        var movie = movies[m];
        var m_showtimes = movie.showtimes;
			  $.each(m_showtimes, function(i, st){

          if(selected_theatres.indexOf(st.theatre) > -1){  //if this showtime's theatre is in the list of seleced theatres
            var show = {};
            show.uuid = guid();
            show.id = movie.id;
            show.title = movie.title;
            show.theatre = st.theatre;
            show.startTime = moment(st.dateTime).format();
            show.runTime = movie.runTime;
            show.minutes = moment.duration(movie.runTime).asMinutes();
            show.endTime = moment(st.dateTime).add(show.minutes, "minutes").format();
            show.ticketURI = st.ticketURI;

            showtimes.push(show);
          }
        });
			});
			// console.log("selected_showtimes");
			// console.log(showtimes);


      //build binges;

      $.each(showtimes, function(i, st){
				// console.log("starting at: "+st.uuid + " - " + st.title+ " - " + st.theatre+ " : " + st.startTime+ " --> " + st.endTime);
        var binge = [];
        binge.push(st);

        //now look for following movies from the whole list, excluding this one...
        $.each(showtimes, function(i2, st2){
  				// console.log(i2 + " - checking: "+st2.uuid + " - " + st2.title);
          // console.log(binge);
          if(st.uuid != st2.uuid){  //if it's not the one we started at...
            //if it starts after the previous one ends, add it to the list, if it's not already in the list
            var start_time = moment(st2.startTime);
            var end_time = moment(binge[binge.length-1].endTime);
            end_time = end_time.add(time_pad, "minutes");

    				// console.log("comparing to: "+st2.uuid + " - " + st2.title+ " - " + st2.theatre+ " : " + end_time.format()+ " - " + start_time.format());

            if(start_time.isAfter(end_time)){
              // console.log("timing's right.");
              if(isInBinge(binge, st2) === false){
                  // console.log("pushing: " + st2.title);
                  binge.push(st2);
              }
            }else{
              // console.log("not adding: " + st2.title);
            }
          }
        });

        binges.push(binge);
      });

      // console.log("BINGES:");
      // console.log(binges);

      trimBinges(); //eliminate singles

    //
			//build display lists
			$("#binge_list").empty();

			var b_list = "";
			$.each(binges, function(i, b){
				// b_list += '<li data-role="collapsible">';
  			// b_list += '<h4>Option '+(i+1)+'</h4>';
  			b_list += '<li data-role="list-divider">Option '+(i+1)+'</li>';
  			// b_list += '<ul data-role="listview">';
  				$.each(b, function(j, st){
  				b_list += '<li>'+moment(st.startTime).format("h:mm a") + " - " + moment(st.endTime).format("h:mm a") + " ~ " + st.title+ "<br><small>" + st.theatre+'</small></li>';
  				});
  			// b_list += '</ul>';
  			b_list += '</li>';
			});

			$(b_list).appendTo( "#binge_list").trigger( "create" );
			$.mobile.navigate("#binges"); //navigate to page
			$('ul').listview().listview("refresh");

		}


    function trimBinges(){
      for (var i = 0; i < binges.length; i++) {
        if(binges[i].length <2 ){
          binges.splice(i, 1);
        }
      }
    }


		function sortTimes(a,b) {
  		// console.log(a.start.valueOf());
  		if (a.start.valueOf() < b.start.valueOf())
  			 return -1;
  		if (a.start.valueOf() > b.start.valueOf())
  			return 1;
  		return 0;
  	}

    function isInBinge(mybinge, st){
      // console.log("checking if "+st.title+ st.id + " is in binge");
      var found = false;
      for (var i = 0; i < mybinge.length; i++) {
        b = mybinge[i]
        if(b.id == st.id){
          // console.log("found: " + st.title);
          found = true;
          break;
        } //if the movie is not already in this binge
        else{
          // console.log(st.title + " NOT found");
        }
      }
      return found;
    }





/////// GENERIC FUNCTIONS

Array.prototype.makeUnique = function(sort)
{
	var n = {},r=[];
	for(var i = 0; i < this.length; i++)
	{
		if (!n[this[i]])
		{
			n[this[i]] = true;
			r.push(this[i]);
		}
	}
  if(sort == true)
    r = r.sort();
	return r;
};

function guid() {
  function s4() {
    return Math.floor((1 + Math.random()) * 0x10000)
      .toString(16)
      .substring(1);
  }
  return s4() + s4() + s4() + s4();
}

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    var expires = "expires="+d.toUTCString();
    document.cookie = cname + "=" + cvalue + "; " + expires;
}

 </script>


</head>

<body>
    <div data-role="page" id="home">
        <div data-role="header">
        	<p>Optimize your movie viewing!</p>
        </div>
        <div role="main" class="ui-content">
            <div id="startSearch" data-role="button" class="ui-btn ui-corner-all ui-btn-b">Click to Start Search</div>
            <!-- <form id='settings' data-ajax="false" > -->

					<label for="loc">Where do you want to watch movies? (city or address):</label>
				<input name="loc" id="loc" value="<?php echo $loc;?>" placeholder="enter location..." type="text">
				<div data-role="button" id="updateLocation" class="ui-btn ui-corner-all ui-btn-a">Get Current Location</div><hr>

				<label for="date">When will you be binging?</label>
				<select name="date" id="date">
				    <option value="<?PHP echo date('Y-m-d', strtotime('today'));?>">Today</option>
				    <option value="<?PHP echo date('Y-m-d', strtotime('+1 day'));?>">Tomorrow</option>
				    <option value="<?PHP echo date('Y-m-d', strtotime('+2 day'));?>"><?PHP echo date('Y-m-d', strtotime('+2 day'));?></option>
				</select>
				<input type="hidden" name="lat" id="lat" value="<?php echo $lat;?>"/>
				<input type="hidden" name="lng" id="lng" value="<?php echo $lng;?>"/>
			<!-- </form> -->
        </div>
        <div data-role="footer"><?PHP include 'footer.php';?></div>
    </div>




    <div data-role="page" id="theatres">
        <div data-role="header">
        	<p>
        		<a href="#home" data-role="button" data-direction="reverse" class="ui-btn ui-corner-all ui-btn-icon-left ui-icon-arrow-l ui-btn-b ">Back</a>
        		Theatres
        	</p>
        </div>
        <div role="main" class="ui-content">
        	<a href="#" onclick="findMoviesInTheatres();" data-role="button" class="ui-btn ui-corner-all ui-btn-icon-left ui-btn-b ui-btn-b">Click to Find Movies</a>
            <form id='theatre_list'>
		    	<label><input type="checkbox" name="0">loading...</label>
			</form>
        </div>
        <div data-role="footer"><?PHP include 'footer.php';?></div>
    </div>




    <div data-role="page" id="movies">
        <div data-role="header">
			<p>
				<a href="#theatres" data-role="button" data-direction="reverse" class="ui-btn ui-corner-all ui-btn-icon-left ui-icon-arrow-l ui-btn-b">Back</a>
        		Movies
        	</p>
        </div>
        <div role="main" class="ui-content">
			<a href="#" onclick="getBinges();" data-role="button" class="ui-btn ui-corner-all ui-btn-icon-left ui-btn-b">Click to See Binges</a>
            <form id='movie_list'>
                <li>loading movies...</li>
            </form>
        </div>
        <div data-role="footer"><?PHP include 'footer.php';?></div>
    </div>





    <div data-role="page" id="binges">
        <div data-role="header">
			<p>
				<a href="#movies" data-role="button" data-direction="reverse" class="ui-btn ui-corner-all ui-btn-icon-left ui-icon-arrow-l ui-btn-b">Back</a>
        		Binges
        	</p>
        </div>
        <div role="main" class="ui-content">
			<!-- <a href="#" onclick="findMoviesInTheatres();" data-role="button">Select some Movies, then click to view possible lineups</a> -->
            <ul id="binge_list" data-inset="true" data-role="listview">

			</ul>
        </div>
        <div data-role="footer"></div>
    </div>


    <!-- hidden -->
    <div data-role="popup" id="myPopupDiv" class="ui-content">
      <!-- <a href="#" data-rel="back" data-role="button" data-theme="a" data-icon="delete" data-iconpos="notext" class="ui-btn-right">Close</a> -->
    	<div id="popup_content">This is an empty popup. How nice!<div>
    </div>


</body>
</html>
