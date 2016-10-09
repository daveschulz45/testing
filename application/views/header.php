<?php 
?>

<!doctype html>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title> <?php echo $title; ?> </title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="apple-touch-icon" href="apple-touch-icon.png">
  
        <link rel="stylesheet" href="Assets/css/accordian-css.css">
        <link rel="stylesheet" href="Assets/css/normalize.css">
        <link rel="stylesheet" href="Assets/css//main.css">
        <script src="Assets/js/vendor/modernizr-2.8.3.min.js"></script>
        <link href="https://fonts.googleapis.com/css?family=Lato|Oswald:700" rel="stylesheet"> 
        <link rel="stylesheet" href="http://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.6.3/css/font-awesome.min.css">

    </head>

    <!-- using HTML5 boilerplate: https://html5boilerplate.com/ -->
    <body>

    <!-- =================================================================== -->
	<!--                        BRANDING / HEADER             	   	         -->
	<!-- =================================================================== -->
        <header class="full-width">
            
            <div class="page-wrap">
                <h1> <?php echo $pageHeader; ?> </h1>
            </div>
            
        </header>
        
        
        
        
    <!-- =================================================================== -->
	<!--                       MAIN CONTENT BEGIN            		         -->
	<!-- =================================================================== -->
        
        
        <!-- ..................USER LOG IN / LOG OUT PANEL...................... -->
        <section class="user-login-panel page-wrap">
            
            <i class="fa fa-user fa-4x" aria-hidden="true"></i>
            
            <!-- [PLACE HOLDER] Lectuer's login email here -->
            <span class="lecture-email">  <?php echo  $userEmail[0]->Email ; ?></span>
            
            <!-- LOG OUT FUNCTIONALITY HERE -->
            <a href="#" class="log-out">log out</a>
             
        </section>
