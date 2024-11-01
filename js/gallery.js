jQuery(document).ready(function($) {
    var thumbnails = $('.simple-sc-gallery img.thumbnail');
    var fullscreens = $('.simple-sc-gallery img.fullscreen');
    var currentIndex = 0;
    var showArrows = simpleScGallerySettings.show_arrows == 1;
    var arrowColor = simpleScGallerySettings.arrow_color;
    var showCloseCross = simpleScGallerySettings.show_close_cross == 1;
    var closeCrossColor = simpleScGallerySettings.close_cross_color;
    var objectFit = simpleScGallerySettings.object_fit;
    var transitionEffect = simpleScGallerySettings.transition_effect;
    var fullscreenBgColor = simpleScGallerySettings.fullscreen_bg_color;
    var fullscreenBgOpacity = simpleScGallerySettings.fullscreen_bg_opacity;

    // Create and style backdrop
    if ($('.simple-sc-gallery .backdrop').length === 0) {
        $('.simple-sc-gallery').append('<div class="backdrop"></div>');
    }
    $('.simple-sc-gallery .backdrop').css({
        'position': 'fixed',
        'top': 0,
        'left': 0,
        'width': '100%',
        'height': '100%',
        'background-color': fullscreenBgColor,
        'opacity': fullscreenBgOpacity,
        'z-index': 9998,
        'display': 'none'
    });

    function applyObjectFit() {
        fullscreens.each(function() {
            $(this).css({
                'object-fit': objectFit,
                'width': '100%',
                'height': '100%'
            });
        });
    }

    function showImage(index) {
        if (index >= thumbnails.length || index < 0) {
            closeImage();
        } else {
            var $current = fullscreens.eq(currentIndex);
            var $next = fullscreens.eq(index);

            // Apply transition effect
            switch (transitionEffect) {
                case 'fade':
                    $current.fadeOut();
                    $next.fadeIn();
                    break;
                case 'threejs':
                    // Initialize Three.js transition effect
                    threeJsTransition($current[0].src, $next[0].src);
                    break;
                            
               case 'twirl':
    $current.removeClass('twirl-effect').hide();  
    
    $next.addClass('twirl-effect').css({
        'transform-origin': 'center center',  
        'transition-duration': '320ms',  
             'top': '5%',
        'left': '5%',
        'transform': 'rotate(-360deg)',  
        'transition-timing-function': 'ease-in-out', 
    }).show(); 
    
    setTimeout(function() {
        $next.css('transform', 'rotate(0deg)');
    }, 50); 
    break;             
                            
               case 'stretch':
    $current.hide(); 
    
    $next.css({
        'transform-origin': 'center center',
            'top': '5%',
        'left': '5%',
       'transform': 'scaleX(0.5)',
        'transition-duration': '300ms',
        'transition-timing-function': 'ease-in-out'
    }).show();
    
    setTimeout(function() {
        $next.css('transform', 'scaleX(1)'); 
    }, 50); 
    break;
                            
                case 'none':
                default:
                    $current.hide();
                    $next.show();
            }

            $('.simple-sc-gallery .backdrop').show(); // Show backdrop
            if ($('.simple-sc-gallery .close').length === 0) {
                $('.simple-sc-gallery').append('<div class="close"></div>');
            }

            if (showCloseCross) {
                $('.simple-sc-gallery .close').show().css('background-color', closeCrossColor);
            } else {
                $('.simple-sc-gallery .close').hide();
            }

             if (showArrows) {
                if ($('.simple-sc-gallery .arrow').length === 0) {
                    $('.simple-sc-gallery').append('<div class="arrow left"></div><div class="arrow right"></div>');
                }
                $('.simple-sc-gallery .arrow').show().css({'color': arrowColor, 'border': '2px solid ' + arrowColor});
            }
            currentIndex = index;
            applyObjectFit();
        }
    }

    function closeImage() {
        fullscreens.hide();
        $('.simple-sc-gallery .backdrop').hide(); // Hide backdrop
        $('.simple-sc-gallery .close, .simple-sc-gallery .arrow').hide();
    }

    thumbnails.on('click', function() {
        var index = thumbnails.index(this);
        showImage(index);
    });

    function showFullscreen(index) {
        currentIndex = index;
        var fullscreenImage = $(fullscreens.get(currentIndex));
        fullscreenImage.css({
            'display': 'block',
            'background-color': fullscreenBgColor,
            'opacity': fullscreenBgOpacity
        });
        $('.simple-sc-gallery .backdrop').show();
    }

    $('.simple-sc-gallery').on('click', '.close', function() {
        closeImage();
    });

    $('.simple-sc-gallery').on('click', '.arrow.left', function() {
        var newIndex = (currentIndex - 1 + thumbnails.length) % thumbnails.length;
        showImage(newIndex);
    });

    $('.simple-sc-gallery').on('click', '.arrow.right', function() {
        var newIndex = (currentIndex + 1) % thumbnails.length;
        showImage(newIndex);
    });

    fullscreens.on('click', function(e) {
        e.stopPropagation();
        var newIndex = (currentIndex + 1) % thumbnails.length;
        showImage(newIndex);
    });

     // Add click event to backdrop to close the image
    $('.simple-sc-gallery').on('click', '.backdrop', function() {
        closeImage();
    });

    $(document).keydown(function(e) {
        if (e.keyCode == 37) {
            var newIndex = (currentIndex - 1 + thumbnails.length) % thumbnails.length;
            showImage(newIndex);
        } else if (e.keyCode == 39) {
            var newIndex = (currentIndex + 1) % thumbnails.length;
            showImage(newIndex);
        } else if (e.keyCode == 27) {
            closeImage();
        }
    });

    applyObjectFit();

    function threeJsTransition(imageOut, imageIn) {
        var root = new THREERoot({
            createCameraControls: false,
            antialias: (window.devicePixelRatio === 1),
            fov: 80
        });

        root.renderer.setClearColor(0x000000, 0);
        root.renderer.setPixelRatio(window.devicePixelRatio || 1);
        root.camera.position.set(0, 0, 60);

        var width = 100;
        var height = 60;

        var slideOut = new Slide(width, height, 'out');
        var l1 = new THREE.ImageLoader();
        l1.setCrossOrigin('Anonymous');
        slideOut.setImage(l1.load(imageOut));
        root.scene.add(slideOut);

        var slideIn = new Slide(width, height, 'in');
        var l2 = new THREE.ImageLoader();
        l2.setCrossOrigin('Anonymous');
        slideIn.setImage(l2.load(imageIn));
        root.scene.add(slideIn);

        var tl = gsap.timeline({repeat: 0, yoyo: false});
        tl.add(slideOut.transition(), 0);
        tl.add(slideIn.transition(), 0);

        createTweenScrubber(tl);

        window.addEventListener('keyup', function(e) {
            if (e.keyCode === 80) {
                tl.paused(!tl.paused());
            }
        });
    }

    
});
