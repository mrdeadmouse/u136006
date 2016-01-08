function showMoreComments(id, source)
{
	var moreButton = BX('blog-comment-more');
	var lastComment = BX("comcntshow").value;
	var urlToMore = BX.message('SBPCurlToMore');
	var url = urlToMore.replace(/#comment_id#/, lastComment);
	url = url.replace(/#post_id#/, id);

	if (moreButton)
	{
		BX.removeClass(moreButton, 'post-comments-button');
		BX.addClass(moreButton, 'post-comments-button-waiter');
	}

	BMAjaxWrapper.Wrap({
		'type': 'json',
		'method': 'GET',
		'url': url,
		'data': '',
		'processData' : true,
		'callback': function(data) 
		{
			if (moreButton)
			{
				BX.removeClass(moreButton, 'post-comments-button-waiter');
				BX.addClass(moreButton, 'post-comments-button');
			}

			if (typeof data.TEXT != 'undefined')
			{
				BX('blog-comment-hidden').innerHTML = data.TEXT + BX('blog-comment-hidden').innerHTML;
				BX('blog-comment-hidden').style.display = "block"; 
				oMSL.parseAndExecCode(data.TEXT);				
			}
			else
			{
				if (moreButton)
				{
					BX.removeClass(moreButton, 'post-comments-button-waiter');
					BX.addClass(moreButton, 'post-comments-button');
				}
			}
		},
		'callback_failure': function(data) {
			if (moreButton)
			{
				BX.removeClass(moreButton, 'post-comments-button-waiter');
				BX.addClass(moreButton, 'post-comments-button');
			}
		}
	});
}