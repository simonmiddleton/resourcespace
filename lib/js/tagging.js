
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

    // TODO - Call API to get a list of matching users and call TaggingSuggest on each.
    TaggingStop(object);
    for (var n=1;n<4;n++)
        {
        TaggingSuggest(object,taggingname + n);
        }
    }

// Add a suggestion hint.
function TaggingSuggest(object,username)
    {
    var TagHint = document.createElement("a");
    TagHint.innerHTML=username;
    TagHint.className='TaggingHint';
    TagHint.href='#';
    TagHint.onclick=function(evt)
        {
        // Auto complete with this username.
        var atsign=object.value.lastIndexOf('@');
        object.value=object.value.substring(0,atsign+1) + this.innerHTML + " ";
        object.focus();
        TaggingStop(object);
        return false;
        }

    console.log("Next sibling: " + object.nextSibling);
    object.parentNode.insertBefore(TagHint,object.nextSibling);
    }

function TaggingStop(object)
    {
    console.log("Tagging stop.");

    // Clear all tag hints
    object.parentNode.childNodes.forEach(TaggingRemoveHint);
    }

function TaggingRemoveHint(item, index)
    {
    console.log(index + item.innerHTML);
    if (item.className=='TaggingHint')
        {
        item.remove();console.log('removed ' + index);
        }
    }