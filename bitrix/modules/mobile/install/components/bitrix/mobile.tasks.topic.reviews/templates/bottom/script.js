function __MB_TASKS_TASK_TOPIC_REVIEWS_RenderComment(arComment, callbackInserter)
{
	var commentNode = null;
	var ratingNode = null;
	var anchor_id = null;	
	var you_like_class = null;

	if ( ! arComment['META:FORMATTED_DATA'] )
		return null;

	anchor_id = Math.floor(Math.random()*100000) + 1;

	if (
		! (
			arComment['POST_MESSAGE_TEXT']
			&& (arComment['POST_MESSAGE_TEXT'].length > 0)
			&& (arComment['ID'] > 0)
		)
	)
	{
		return null;
	}

	var ratingTypeId = 'FORUM_POST';
	var entityTypeId = ratingTypeId;
	var eventEntityId = arComment['ID'];
	var ownerId = arComment['AUTHOR_ID'];

	var allowRatingVote = (arComment['META:ALLOW_VOTE_RATING']['RESULT'] ? 'Y' : 'N');

	if (typeof (arComment['RATING']['TOTAL_POSITIVE_VOTES']) == 'undefined')
	{
		arComment['RATING']['TOTAL_POSITIVE_VOTES'] = 0;
	}

	var you_like_class = (
		arComment['RATING']['USER_HAS_VOTED'] === 'Y'
			? 'post-comment-likes-liked'
			: 'post-comment-likes'
	);

	var vote_id = ratingTypeId
		+ '-' + eventEntityId
		+ '-' + anchor_id;

	ratingNode = BX.create('div', {
		props: {
			'id': 'bx-ilike-button-' + vote_id,
			'className': you_like_class
		},
		children: [
			BX.create('div', {
				props: {
					'className': 'post-comment-likes-text'
				},
				html: BX.message('RVCText')
			}),
			BX.create('div', {
				props: {
					'id': 'bx-ilike-count-' + vote_id,
					'className': 'post-comment-likes-counter'
				},
				html: '' + parseInt(arComment['RATING']['TOTAL_POSITIVE_VOTES']) + ''
			})
		]
	});

	if (arComment['AUTHOR_PHOTO'] 
		&& (arComment['AUTHOR_PHOTO'] != 'undefined')
	)
	{
		var avatar = BX.create(
			'div', 
			{
				props: { 'className': 'avatar' }, 
				style: { 
					backgroundImage: "url('" + arComment['AUTHOR_PHOTO'] + "')",
					backgroundRepeat: "no-repeat",
					backgroundSize: "29px 29px"
				}
			}
		);
	}
	else
	{
		var avatar = BX.create(
			'div', {
				props: { 'className': 'avatar' } 
			}
		);
	}

	if (arComment['META:FORMATTED_DATA']['DATETIME_SEXY'] != 'undefined')
		comment_datetime = arComment['META:FORMATTED_DATA']['DATETIME_SEXY'];
	else
		comment_datetime = '';

	class_name_unread = '';

	commentNode = BX.create('div', {
		props: { 
			'id'       : 'tasks-comment-block-' + arComment['ID'],
			'className': 'post-comment-block'
		},
		children: [
			BX.create('div', {
				props: { 'className': 'post-user-wrap' },
				children: [
					avatar,
					BX.create('div', {
						props: { 'className': 'post-comment-cont' },
						children: [
							BX.create('a', {
								props: { 'className': 'post-comment-author' },
								attrs: { 'href': arComment['META:FORMATTED_DATA']['AUTHOR_URL'] },
								html: arComment['META:FORMATTED_DATA']['AUTHOR_NAME']
							}),
							BX.create('div', {
								props: { 'className': 'post-comment-time' },
								html: comment_datetime
							})
						]
					})
				]
			}),
			BX.create('div', {
				props: { 'className': 'post-comment-text' },
				html: arComment['POST_MESSAGE_TEXT']
			}),
			ratingNode
		]
	});

	callbackInserter(commentNode, ratingNode, vote_id, ratingTypeId, eventEntityId, allowRatingVote, arComment['ID'], arComment['POST_MESSAGE_TEXT']);

	return (commentNode);
}


function __MB_TASKS_TASK_TOPIC_REVIEWS_ShowComments(data)
{
	if ( ! data.arComments )
		return;

	arComments = data.arComments;

	var commentNode = null;
	var arComment = null;

	for (var indx in arComments)
	{
		if ( ! arComments.hasOwnProperty(indx) )
			continue;

		arComment = arComments[indx];

		commentNode = __MB_TASKS_TASK_TOPIC_REVIEWS_RenderComment(
			arComment,
			function(comNode, ratingNode, vote_id, ratingTypeId, eventEntityId, allowRatingVote, commentId, commentText)
			{
				if (comNode)
				{
					BX('post-comment-hidden').appendChild(comNode);

					var tempDiv = document.createElement("div");
					tempDiv.innerHTML = commentText;

					var arScripts = tempDiv.getElementsByTagName("SCRIPT");
					var script = null;
					for(var i = arScripts.length - 1; i >= 0; i--)
					{
						script = BX.create(
							"script", {
								props : {type : "text/javascript"},
								html: arScripts[i].innerHTML
							}
						);

						comNode.parentNode.insertBefore(script, comNode.nextSibling);
					}
				}

				if (ratingNode)
				{
					if (!window.RatingLikeComments && top.RatingLikeComments)
						RatingLikeComments = top.RatingLikeComments;

					RatingLikeComments.Set(
						vote_id,
						ratingTypeId,
						eventEntityId,
						allowRatingVote
					);
				}
			}
		);
	}

	BX('post-comment-hidden').style.display = "block";
	BX('post-comment-more').style.display = "none";
}

function __MB_TASKS_TASK_TOPIC_REVIEWS_scrollPageBottom()
{
	if (window.platform == "android")
	{
		window.scrollTo(0, document.documentElement.scrollHeight);
	}
	else
	{
		var div = BX('tasks-detail-card-container-over');
		div.scrollTop = div.scrollHeight - div.offsetHeight;
	}
}

function __MSLCheckVisibility()
{
	return (true);
}

__MB_TASKS_TASK_TOPIC_REVIEWS_viewImageBind = function(div, isTarget)
{
	if (app.enableInVersion(6))
	{
		var div = BX(div);
		if (!!div)
		{
			BX.bindDelegate(div, 'click', isTarget, function(e)
			{
				var imgNodeList = BX.findChildren(div, isTarget, true),
					imgList = [],
					currentImage = false;

				var arPhotos = [];
				for(var i=0; i<imgNodeList.length; i++)
				{
					currentImage = imgNodeList[i].getAttribute('data-bx-image');
					if (!BX.util.in_array(currentImage, imgList))
					{
						imgList[imgList.length] = imgNodeList[i].getAttribute('data-bx-image');
						arPhotos[arPhotos.length] = {
							"url": currentImage,
							"description": ""
						};
					}
				}

				app.openPhotos({
					"photos": arPhotos
				});

				return BX.PreventDefault(e);
			});
		}
	}
}
