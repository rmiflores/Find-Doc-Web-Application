<html>

<?php include("include/head.html") ?>

<body>

	<div data-role = "page" id = "profile<?php echo $_GET["id"] ?>">

		<div data-role = "header" data-theme = "b" data-position = "fixed">
			<h1> Doctor Profile </h1>
			<a data-rel = "back" data-role = "button" data-icon = "arrow-l" data-transition = "slide" data-theme = "a">Back </a>
			<a href = "index.php" data-role = "button" data-icon = "home" data-transition = "slide" data-theme = "a" data-direction="reverse"> Home </a>
		</div>
		<div data-role = "content">

			<form style = "display: none">
				<input class = "latitude" name = "latitude">
				<input class = "longitude" name = "longitude">
			</form>

			<?php include("include/fav-link.html"); ?>
			
			<div class = "businessCard">
				<?php
					include("include/config.php");
					$id = $_GET["id"];
					$query = "SELECT * FROM doctors WHERE id = '".$id."'";
					$result = mysql_query($query);
					if ($result && mysql_num_rows($result) != 0) {
						$row = mysql_fetch_assoc($result);
						
						$latitude = $row["latitude"];
						$longitude = $row["longitude"];
						
						include("include/phone.php");
						echo "<h3> ".$row["name"]." </h3>";
						echo "<div class = 'doctor-details'>";
						echo "<div class = 'img-wrapper'><img src = '".$row["image"]."'></div>";
						echo "<p> Specialty: ".$row["specialties"]."<br>";
						echo "Phone: <a href='tel:+1".$row["phone"]."'>".$phone."</a><br>";
						echo "Hours: ".$row["hours"]."<br>";
						echo "Distance: <span class = 'distance'></span><br>";
						echo "</p></div>\n";
						echo "<span class = 'rating rating-profile'>".$row["rating"]."</span>";
						echo "<span class = 'profileButtons'>";
							echo "<a href = '#ratePopup' data-rel = 'popup' data-role = 'button' data-theme = 'b' data-transition = 'pop' data-inline = 'true'> Rate </a>";
							echo "<a data-role = 'button' data-theme = 'b' data-transition = 'slide' data-inline = 'true' class = 'addToFavButton'> Save </a>";
						echo "</span>\n";

						echo "<div id = 'map_canvas".$id."' class = 'map_canvas'></div>";
						echo "<a data-role = 'button' data-theme = 'b' href = 'http://maps.apple.com/maps?q=".$row['latitude'].','.$row['longitude']."'> Open in Maps.app </a>";

						echo "<h3 id = 'comments-title'> Comments </h3>";
						echo "<div class = 'comments-container'></div>";
					} else {
						echo "Not found.";
					}
				?>

			</div>

			<div data-role = "popup" data-overlay-theme = "a" id = "ratePopup">

				<a data-rel="back" data-role="button" data-theme="a" data-icon="delete" data-iconpos="notext" class="ui-btn-right">Close</a>

				<form id = "rate-form<?php echo $id ?>" action = "submit-rating.php" method = "post">
					<?php
						echo "<input name = 'id' value = '".$_GET["id"]."' style = 'display: none'>";
					?>
					<label for = "rating"> Rating (1 = poor, 5 = outstanding): </label>
					<input type = "range" name = "rating" min = "0" max = "5" data-highlight = "true" data-theme = "b" data-track-theme = "b" required>

					<label for = "comment"> Comments: </label>
					<textarea name = "comment" id = "comment"> </textarea>

					<span class = "rateButtons">
						<input type = "submit" value = "Submit" data-type = "horizontal" data-inline = "true" data-theme = "b">
						<a data-rel="back" data-role="button" data-theme="b" class="ui-btn-right">Cancel</a>
					</span>
				</form>
			</div>

			<script>
				<?php include("include/stars.html") ?>

				$("#profile<?php echo $id ?>").die("pagebeforeshow"); // Remove any previous bindings so that the code is not run twice

				$("#profile<?php echo $id ?>").live("pagebeforeshow", function() {
					$(".comments-container").html("");
					loadMoreComments(); // Calling the function also sets up the binding to loadMoreIfAtBottom

					$(".addToFavButton").click(function() {
						$.get("addToFav.php?id=<?php echo $id ?>", function(data) {
							if ($(".profile-img-wrapper-fav").length == 0) { // If not already animating
								var left = $(".img-wrapper").position().left / $(".img-wrapper").parent().width() * 100;
								$(".img-wrapper").css("left", left + "%"); // Explicitly set left as a percentage to allow the browser to animate to left: 100%
								$.get("addToFav.php?id=<?php echo $id ?>", function(data) {
									$('.fav-link').addClass("fav-highlight");
									$(".doctor-details img").addClass("profile-img-fav");
									$(".img-wrapper").addClass("profile-img-wrapper-fav");
									window.setTimeout(function() {
										$(".doctor-details img").removeClass("profile-img-fav");
										$(".img-wrapper").removeClass("profile-img-wrapper-fav");
									}, 1000);
									window.setTimeout(function() {
										$('.fav-link').removeClass("fav-highlight");
									}, 2000);
								});
							}
						});
					});

					// Load more results if we are at the bottom
					function loadMoreComments() {
						$(window).unbind('scroll');
						$(".profile").append("<div class = 'loading'>Loading</div>");
						page = Math.ceil($(".comment").length / 10);
						$.get('loadMoreComments.php?id=<?php echo $id ?>&page=' + page, function(data) {
						 	if (data != "") {
								$(".comments-container").append(data);
								$(window).scroll(loadMoreCommentsIfAtBottom);
							}
							$(".loading").remove();
						});
					}

					function loadMoreCommentsIfAtBottom() {
						if ($(window).scrollTop() + $(window).height() >= $(document).height()) {
							loadMoreComments();
						}
					}

					$("#rate-form<?php echo $id ?>").unbind("submit");

					$("#rate-form<?php echo $id ?>").submit(function() {
						$.post("submit-rating.php", $("#rate-form<?php echo $id ?>").serialize(), function() {
							<?php
								echo "window.location.href = 'profile.php?id=".$_GET["id"]."';\n"; // Go back
							?>
						});
						return false;
					});
				});


				$("#profile<?php echo $id ?>").live("pageshow", function() {
					var mapOptions = {
						center: new google.maps.LatLng(<?php echo $latitude ?>, <?php echo $longitude ?>),
						zoom: 8,
						mapTypeId: google.maps.MapTypeId.ROADMAP
					};
					window.googleMap<?php echo $id ?> = new google.maps.Map($("#map_canvas<?php echo $id ?>")[0], mapOptions);
					var docCoords = new google.maps.LatLng(<?php echo $latitude?>,<?php echo $longitude?>);
					var docMarker = new google.maps.Marker({
						position: docCoords,
						map: window.googleMap<?php echo $id ?>,
						title: "Your doctor's location"
					});



					function getDistance(data) {
						$.post("getdistance.php", data, function(data) {
							$(".distance").html(data + " mi");
							window.googleMap<?php echo $id ?>.setZoom(Math.round(8 + 4 / data));
						});
					}

					if (navigator.geolocation) {
						navigator.geolocation.getCurrentPosition(function (position) {
							$(".latitude").val(position.coords.latitude);
							$(".longitude").val(position.coords.longitude);

							var currentPosition = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
							window.googleMap<?php echo $id ?>.setCenter(currentPosition);

							getDistance({
								id: <?php echo $_GET["id"] ?>,
								latitude: position.coords.latitude,
								longitude: position.coords.longitude
							});
						}, function () {
							<?php
								if ($_SERVER['SERVER_NAME'] != "localhost") {
									$url = "http://api.ipinfodb.com/v3/ip-city/?key=16ceb4e81c46df1a31558904f1da1f79e2edabc509f4ec44bdc8c169fb71a193&format=xml&ip=".$_SERVER["REMOTE_ADDR"];
									$xml = simplexml_load_file($url);
									echo "$('.latitude').val(".$xml->latitude.");";
									echo "$('.longitude').val(".$xml->longitude.");";
									echo "getDistance({";
										echo "id: ".$_GET["id"].",";
										echo "latitude: ".$xml->latitude.",";
										echo "longitude: ".$xml->longitude;
									echo "});";
								}
							?>
						});
					}
				});

				</script>

		</div>
		<br>
	</div>

</body>
</html>