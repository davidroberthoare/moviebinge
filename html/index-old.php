<?PHP  
	session_start();
	$loc = 	isset($_COOKIE['loc']) ? $_COOKIE['loc'] : "";
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
  
  <link rel="stylesheet" href="http://code.jquery.com/mobile/1.4.3/jquery.mobile-1.4.3.min.css" />
  <script src="http://code.jquery.com/jquery-1.11.1.min.js"></script>
  <script src="http://code.jquery.com/mobile/1.4.3/jquery.mobile-1.4.3.min.js"></script>
  <script src="moment.min.js"></script>
  <script src="cookies.js"></script>
  
  
  <style>
  	html{
		/*text-align:center;	*/
	}
  </style>
  
  
  <script>
  	//main functionality
  	
  	var movie_data;
  	var selected_theatres = [];
  	var movie_list = [];	//list of movie names only, for display
  	var showtimes = [];	//will hold all showtimes of selected movies at selected theatres, sorted chronologically
  	var d = new Date();
	var today = d.toDateString();
  	
  	$( document ).ready(function() {
		$("#settings").submit(function(){
		  	saveSettings();
		  	return false;
		}); 
		
	});
	
	function saveSettings(){
		if($("#loc").val() == ""){
			console.log("no location info");
			toast("Please enter a Location");
			return(false);		
		}
			
		$.mobile.loading( "show", {
	            text: "loading showtimes for that area",
	            textVisible: true
	    });
		var gUrl = "gettimes.php";
        $.getJSON( gUrl, { 
        	loc: ($("#loc").val() != "")  ? $("#loc").val() : "toronto, ON",
        	date:($("#date").val() != "")  ? $("#date").val() : 1
        	// ,refresh:"true" 
        } )
		.done(function( json ) {
			console.log( "JSON Data: " );
			console.log( json);
			if(json.error == false){
				$("#theatre_list").empty(); //clear the list
				//set the main data
				movie_data = json.data;
				$.each( movie_data, function( i, theatre ) {
					// console.log(theatre);
					var theatre_row = '<label><input type="checkbox" value="'+i+'">'+theatre.name+'<br><small>'+theatre.address+'</small></label>';
					$( theatre_row).appendTo( "#theatre_list").trigger( "create" );
				});
				
				//done, so navigate to next step
				docCookies.setItem("loc", $("#loc").val());	//set the cookie for next time...
				$.mobile.loading( "hide");
				$.mobile.navigate("#theatres"); //navigate to page
				
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
	
	
    function setTheatres(){
    	selected_theatres = [];
    	console.log("setting theatres");	
		var theatre_ids = $('#theatre_list input:checkbox:checked').map(function() {
		    return this.value;
		}).get();
		if(theatre_ids.length == 0){
			console.log("no theatres");
			toast("Please select a theatre");
			return(false);		
		}
		$.each(theatre_ids, function(i, id){
			selected_theatres.push(movie_data[id]);
		});
		// console.log(selected_theatres);
		
		movie_list = [];
		$.each(selected_theatres, function(i, t){
			if(t.movies !== undefined){
				$.each(t.movies, function(j, m){
					// console.log(m.title);
					if ($.inArray(m.title,movie_list) == -1){
						// console.log("NOT FOUND!");
					    movie_list.push(m.title);	//just for display purposes
					}
					
					//create and add the movie time info to the showtimes array anyway
					var m_length = m.length[1];	//minutes
					$.each(m.times, function(k, st){
						// console.log(st + " pm");
						start_time = moment( today + " " + st + " pm"); 
						end_time = moment(start_time).add('minutes', m_length);
						showing = {
							title: m.title,
							length: m_length,
							start: start_time,
							end: end_time,
							theatre: t.name
						};
						showtimes.push(showing);							
					});
				});
			}
		});
		movie_list.sort();
		// console.log(movie_list);

		showtimes.sort(sortTimes);
		// console.log(showtimes);
		
		$("#movie_list").empty();
		$.each(movie_list, function(i, m){
			var movie_row = '<label><input type="checkbox" value="'+m+'">'+m+'</label>';
			$( movie_row).appendTo( "#movie_list").trigger( "create" );
		});
		
		
    	$.mobile.navigate("#movies"); //navigate to page
    }
    
    
    function findNextMovies(binge){
    	console.log("finding next movie");
    	showtime_loop:
    	for (var i=0; i < selected_showtimes.length; i++) {
		  	var st = selected_showtimes[i];
		  	
		  	
		  	//test movie hasn't been added already to the current binge
		  	binge_loop:
		  	for (var j=0; j < binge.length; j++) {
		  		var b_movie = binge[j];
		  		if(st.title == b_movie.title)
		  			continue showtime_loop;
		  	}
		  	//then test times
		  	console.log(st.start.format("h:mm a")+ " - " + start_timer.format("h:mm a"));
			if(st.start.valueOf() > start_timer.valueOf()){
				return st;
			}else{
				console.log("not earlier than");	
			}
		};
    	return false;
    }
    
    
    
    var binges = [];
    var start_timer = moment();
    var next_movie = true;
    var selected_movie_list = [];
    var selected_showtimes = [];
    
    function getBinges(){
    	binges = [];
		selected_movie_list = [];
		selected_showtimes = [];
    	start_timer = moment();
    	next_movie = true;
    	
    	selected_movies = [];
    	// console.log("selecting movies");	
		selected_movie_list = $('#movie_list input:checkbox:checked').map(function() {
		    return this.value;
		}).get();
		
		if(selected_movie_list.length == 0){
			console.log("no movies");
			toast("Please select some movies");
			return(false);		
		}
    	
    	
    	//filter the showtimes to only those movies selected
    	// console.log("filtering movies to selected");
    	$.each(showtimes, function(i, st){
    		if($.inArray(st.title, selected_movie_list) > -1){
    			selected_showtimes.push(st);	
    		}
    	});
    	console.log("selected_showtimes");
    	console.log(selected_showtimes);
    	
//     	
		
		
		console.log("building binges");
		// while(searching == true){    	
    	
    	$.each(selected_showtimes, function(i, st){
    		console.log("starting at: "+i + " - " + st.title);
	    	var binge = [];
	    	next_movie = true;
    		//start with the first movie in the list
    		binge.push(st);
    		//advance the start_timer to the end_time of that movie
    		start_timer = st.end;
    		
    		//look for the fist movie that starts right after it
			while(next_movie != false){
				next_movie = findNextMovies(binge);
				if(next_movie != false){
					start_timer = next_movie.end;	
					console.log("adding movie: " );
					console.log(next_movie);
					binge.push(next_movie);
				}
			}
    		//if it's not in this binge already, add it
    		
    		//advance the start_timer to the end_time of that movie we just added

	    	binges.push(binge);
    			
    	});
	    	
	    	// console.log(binges);
	    	// searching = false;	//only do it once
	    // }
    	
    	//build display lists
    	$("#binge_list").empty();
    	
    	var b_list = "";
    	$.each(binges, function(i, b){
	    	// b_list += '<li data-role="collapsible">';
			// b_list += '<h4>Option '+(i+1)+'</h4>';
			b_list += '<li data-role="list-divider">Option '+(i+1)+'</li>';
			// b_list += '<ul data-role="listview">';
	    	$.each(b, function(j, st){
				b_list += '<li>'+st.start.format("h:mm a") + " - " +st.end.format("h:mm a") + " ~ " + st.title+ "<br><small>" + st.theatre+'</small></li>';
	    	});
			// b_list += '</ul>';
			b_list += '</li>';
    	});
    	
    	$(b_list).appendTo( "#binge_list").trigger( "create" );
    	$.mobile.navigate("#binges"); //navigate to page
    	$('ul').listview().listview("refresh");
    	
    }
    
    
    function sortTimes(a,b) {
	  // console.log(a.start.valueOf());
	  if (a.start.valueOf() < b.start.valueOf())
	     return -1;
	  if (a.start.valueOf() > b.start.valueOf())
	    return 1;
	  return 0;
	}
    
    
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
	}
    
    
    
  </script>
  
  
  
</head>

<body>
    <div data-role="page" id="home">
        <div data-role="header">
        	<p>MovieBinge</p>
        </div>
        <div role="main" class="ui-content">
        	<h3>This app will help you find the optimum schedule for your movie binging. Enjoy!</h3>
            <a href="#" onclick="saveSettings();" data-role="button" class="ui-btn ui-corner-all ui-btn-icon-left ui-btn-b">Click to find Showtimes</a>
            <form id='settings' >
		    	<label for="loc">Where do you want to watch movies? (city or address):</label>
				<input name="loc" id="loc" value="<?php echo $loc;?>" placeholder="enter location..." type="text">
		    	<label for="date">What day do you want times for?</label>
				<select name="date" id="date">
				    <option value="0">Today</option>
				    <option value="1">Tomorrow</option>
				    <option value="2"><?PHP echo date('l', strtotime("+2 day"));?></option>
				</select>
			</form>       
        </div>
        <div data-role="footer"><?PHP include("footer.php");?></div>
    </div>
    
    
    
    
    <div data-role="page" id="theatres">
        <div data-role="header">
        	<p>
        		<a href="#home" data-role="button" data-direction="reverse" class="ui-btn ui-corner-all ui-btn-icon-left ui-icon-arrow-l ui-btn-b ">Back</a>
        		Theatres
        	</p>
        </div>
        <div role="main" class="ui-content">
        	<a href="#" onclick="setTheatres();" data-role="button" class="ui-btn ui-corner-all ui-btn-icon-left ui-btn-b ui-btn-b">Click to find Movies</a>
            <form id='theatre_list'>
		    	<label><input type="checkbox" name="0">loading...</label>
			</form>       
        </div>
        <div data-role="footer"><?PHP include("footer.php");?></div>
    </div>
    
    
    
    
    <div data-role="page" id="movies">
        <div data-role="header">
			<p>
				<a href="#theatres" data-role="button" data-direction="reverse" class="ui-btn ui-corner-all ui-btn-icon-left ui-icon-arrow-l ui-btn-b">Back</a>
        		Movies
        	</p>
        </div>
        <div role="main" class="ui-content">
			<a href="#" onclick="getBinges();" data-role="button" class="ui-btn ui-corner-all ui-btn-icon-left ui-btn-b">Click to view schedules</a>
            <form id='movie_list'>
                <li>loading movies...</li>
            </form>        
        </div>
        <div data-role="footer"><?PHP include("footer.php");?></div>
    </div>
    
    
    
    
    
    <div data-role="page" id="binges">
        <div data-role="header">
			<p>
				<a href="#movies" data-role="button" data-direction="reverse" class="ui-btn ui-corner-all ui-btn-icon-left ui-icon-arrow-l ui-btn-b">Back</a>
        		Binges
        	</p>
        </div>
        <div role="main" class="ui-content">
			<!-- <a href="#" onclick="setTheatres();" data-role="button">Select some Movies, then click to view possible lineups</a> -->
            <ul id="binge_list" data-inset="true" data-role="listview">
			  
			</ul>        
        </div>
        <div data-role="footer"></div>
    </div>
    
</body>


 <?PHP 
  	if($loc == ""){	//only use geocode if there is no cookie from pevious visits
  ?>
	  <script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script> 
	  <script>
	  	if(navigator.geolocation) {

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
				        console.log(result.formatted_address);
				        $("#loc").val(result.formatted_address);
			        }
			
			    });
			}, function() {
		      handleNoGeolocation(true);
		    });

		}else {
		    // Browser doesn't support Geolocation
		    handleNoGeolocation(false);
		  }
		
		function handleNoGeolocation(errorFlag) {
		  if (errorFlag) {
		    toast("We can't detect your location.<br>Please enter it manually.");
		  } else {
		    toast("Your browser doesn't allow geolocation.<br>Please enter it manually.");
		  }
		  $("#loc").prop("placeholder","enter your location...");
		}
		
	  </script>
  <?PHP
   	} 
   ?>

</html>
