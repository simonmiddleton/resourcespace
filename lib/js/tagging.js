
// Comment tagging
// Detect the first couple of characters during tagging and pass it to the TaggingLookup function, which will provide
// some suggestions to the user.

function TaggingProcess(object)
    {
    // Look for the @ sign
    var atsign=object.value.lastIndexOf('@');
    var lastspace=object.value.lastIndexOf(' ');
    if (atsign>-1 && lastspace<atsign) // Must be in the midst of typing a tag, i.e. no space.
        {
        var taggingname=object.value.substring(atsign+1);
        if (taggingname.length>=2) {TaggingLookup(object,taggingname);}
        }
    else    
        {
        // No longer tagging, cancel any tagging hints
        TaggingStop(object);
        }
    }

// Search for taggingname in the users
function TaggingLookup(object,taggingname)
    {
    console.log("TaggingLookup:" + taggingname);

    TaggingSuggest(object,taggingname);

    }

// Add a suggestion hint.
function TaggingSuggest(object,username)
    {
    var TagHint = document.createElement("a");
    TagHint.innerHTML=username;
    TagHint.className='TaggingHint';
    TagHint.onclick=function(evt)
        {
        // Auto complete with this username.
        }

    console.log("Next sibling: " + object.nextSibling);
    object.parentNode.insertBefore(TagHint,object.nextSibling);
    }

function TaggingStop(object)
    {
    console.log("Tagging stop.");
    }