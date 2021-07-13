// This is to extend the Hybrid question type
// It's binding some events on custom elements on the file manager events to have a custom UI

M.qtype_hybrid = {};

M.qtype_hybrid.init = function(Y, options) {
    var currentImageName = '';

    var requiredFiles = Y.all('.js-edit-file.is-required');

    // Edit an existing file, will trigger the click of when you click on an image in the File manager
    // It will open a lightbox where you can Delete or update the image
    // If the slot is empty, it will add a new file
    var editFiles = Y.all('.js-edit-file');
    editFiles.each(function (editFile) {
        editFile.on('click', function(node) {
            if (editFile.one('img')) {
                var fileSrc = editFile.one('img').get('src');
                var fileSrcArray = fileSrc.split('/');

                var thumbnailFileManager = Y.one('.fp-thumbnail img[title*="' + decodeURI(fileSrcArray[fileSrcArray.length-1]) + '"]');
                
                thumbnailFileManager.simulate('click');
                currentImageName = fileSrcArray[fileSrcArray.length-1];
            } else {
                var addNewButton = Y.one('.fp-btn-add .btn');
                addNewButton.simulate('click');
            }
            
        });
    });

    // Add a new file, will trigger the click of the Add new file from File manager
    var addFile = Y.one('.js-add-file');
    addFile.on('click', function(node) {
        var addNewButton = Y.one('.fp-btn-add .btn');
        addNewButton.simulate('click');
    });


    // Listen to add ajax requests on the page
    var origOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function() {
        this.addEventListener('load', function() {
            // Deleting an existing image
            if (this.responseURL.indexOf('?action=delete') > -1) {
                Y.one('.js-edit-file img[src*="' + currentImageName + '"]').remove();
                currentImageName = '';
            }

            // Retrieving all images from File manager
            if (this.responseURL.indexOf('draftfiles_ajax.php?action=list') > -1) {
                var response = JSON.parse(this.responseText);

                // Order by date
                var responseOrderedDate = response.list.sort((a,b) => (a.datecreated > b.datecreated) ? 1 : ((b.datecreated > a.datecreated) ? -1 : 0))
                var placeholders = Y.all('.qtype_hybrid_placeholder');

                // Remove all images
                var placeholderImages = Y.all('.qtype_hybrid_placeholder img');
                placeholderImages.each(function (placeholderImage) {
                    placeholderImage.remove();
                });
                
                placeholders.each(function (placeholder, placeholderIndex) {
                    var placeholderImage = placeholder.one('img');
                    if (responseOrderedDate[placeholderIndex]) {
                        if (placeholderImage) {
                            placeholderImage.set('src', responseOrderedDate[placeholderIndex].url);
                        } else {
                            placeholder.append("<img src='" + responseOrderedDate[placeholderIndex].url + "' />");
                        } 
                    }                       
                });

                if (editFiles._nodes.length === responseOrderedDate.length) {
                    Y.one('.js-add-file').hide();
                } else {
                    Y.one('.js-add-file').show();
                }
                
                // Check if required files are uploaded, if not, remove the Check icon
                if (response.filecount < requiredFiles._nodes.length) {
                    Y.one('.js-required-files .icon').hide();
                    Y.one('.js-required-files').removeClass('is-complete');
                } else {
                    Y.one('.js-required-files .icon').show();
                    Y.one('.js-required-files').addClass('is-complete');
                }

                // Check if maximum files has been reached, if not, remove the Check icon
                if (response.filecount !== placeholders._nodes.length) {
                    Y.one('.js-maximum-files .icon').hide();
                    //Y.one('.js-maximum-files').removeClass('is-complete');
                } else {
                    Y.one('.js-maximum-files .icon').show();
                    //Y.one('.js-maximum-files').addClass('is-complete');
                }

            }
            
        });
        origOpen.apply(this, arguments);
    };
};

M.qtype_hybrid.init(Y);
