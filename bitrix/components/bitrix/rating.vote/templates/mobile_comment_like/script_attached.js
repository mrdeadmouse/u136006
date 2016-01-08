if (!BXRL)
{
	var BXRL = {};
}

if (typeof bRatingLikeCommentsInited == 'undefined')
{
	var bRatingLikeCommentsInited = true;

	BX.addCustomEvent("onPull-main", function(data)
	{
		if (data.command == 'rating_vote')
		{
			RatingLikeComments.LiveUpdate(data.params);
		}
	});
}

RatingLikeComments = function(likeId, entityTypeId, entityId, available)
{	
	this.enabled = true;
	this.entityTypeId = entityTypeId;
	this.entityId = entityId;
	this.available = (available == 'Y');

	this.box = BX('bx-ilike-button-' + likeId);
	this.countText = BX('bx-ilike-count-' + likeId);

	if (this.box === null)
	{
		this.enabled = false;
		return false;
	}

	this.likeTimeout = false;	
	this.lastVote = BX.hasClass(this.box, 'post-comment-likes-liked') ? 'plus' : 'cancel';
}

RatingLikeComments.Set = function(likeId, entityTypeId, entityId, available)
{
	BXRL[likeId] = new RatingLikeComments(likeId, entityTypeId, entityId, available);
	if (BXRL[likeId].enabled)
	{
		RatingLikeComments.Init(likeId);	
	}
};

RatingLikeComments.Init = function(likeId)
{
	if (BXRL[likeId].available)
	{
		BX.bind(BXRL[likeId].box, 'click', function(e) 
		{
			clearTimeout(BXRL[likeId].likeTimeout);

			var counterValue = (
				typeof BXRL[likeId].countText.innerHTML == 'undefined'
				|| BXRL[likeId].countText.innerHTML == null
				|| BXRL[likeId].countText.innerHTML == ''
					? 0
					: parseInt(BXRL[likeId].countText.innerHTML)
			);

			var oldValue = (
				BX.hasClass(BXRL[likeId].box, 'post-comment-likes-liked') 
					? 'plus' 
					: 'cancel'
			);

			var newValue = (
				oldValue == 'plus'
					? 'cancel' 
					: 'plus'
			);

			BXRL[likeId].countText.innerHTML = (
				oldValue == 'plus'
					? counterValue - 1
					: counterValue + 1
			);

			BX.removeClass(BXRL[likeId].box, (
				oldValue == 'plus'
					? 'post-comment-likes-liked'
					: 'post-comment-likes'
			));

			BX.addClass(BXRL[likeId].box, (
				oldValue == 'plus'
					? 'post-comment-likes'
					: 'post-comment-likes-liked'
			));

			BXRL[likeId].likeTimeout = setTimeout(function()
			{
				if (BXRL[likeId].lastVote != newValue)
				{
					RatingLikeComments.Vote(likeId, newValue);
				}
			}, 1000);

			BX.PreventDefault(e);
		});
		
	}
}

RatingLikeComments.Vote = function(likeId, voteAction)
{
	var BMAjaxWrapper = new MobileAjaxWrapper;
	BMAjaxWrapper.Wrap({
		'type': 'json',
		'method': 'POST',
		'url': '/mobile/ajax.php?mobile_action=like',
		'data': {
			'RATING_VOTE': 'Y', 
			'RATING_VOTE_TYPE_ID': BXRL[likeId].entityTypeId, 
			'RATING_VOTE_ENTITY_ID': BXRL[likeId].entityId, 
			'RATING_VOTE_ACTION': voteAction,
			'sessid': BX.message('RVCSessID')
		},
		'callback': function(data) {
			if (
				typeof data != 'undefined'
				&& typeof data.action != 'undefined'
				&& typeof data.items_all != 'undefined'
			)
			{
				BXRL[likeId].lastVote = data.action;
				BXRL[likeId].countText.innerHTML = data.items_all;
			}
			else
			{
				BX.removeClass(BXRL[likeId].box, (
					voteAction == 'plus'
						? 'post-comment-likes-liked'
						: 'post-comment-likes'
				));

				BX.addClass(BXRL[likeId].box, (
					voteAction == 'plus'
						? 'post-comment-likes'
						: 'post-comment-likes-liked'
				));

				var newValue = (
					voteAction == 'plus' 
						? (parseInt(BXRL[likeId].countText.innerHTML) - 1)
						: (parseInt(BXRL[likeId].countText.innerHTML) + 1)
				);
				BXRL[likeId].countText.innerHTML = newValue;
			}
		},
		'callback_failure': function(data)
		{
			BX.removeClass(BXRL[likeId].box, (
				voteAction == 'plus'
					? 'post-comment-likes-liked'
					: 'post-comment-likes'
			));

			BX.addClass(BXRL[likeId].box, (
				voteAction == 'plus'
					? 'post-comment-likes'
					: 'post-comment-likes-liked'
			));

			var newValue = (
				voteAction == 'plus'
					? (parseInt(BXRL[likeId].countText.innerHTML) - 1)
					: (parseInt(BXRL[likeId].countText.innerHTML) + 1)
			);
			BXRL[likeId].countText.innerHTML = newValue;
		}
	});

	return false;
}

RatingLikeComments.List = function(likeId)
{
	if (app.enableInVersion(2))
	{
		app.openTable({
			callback: function() {},
			url: (BX.message('MobileSiteDir') ? BX.message('MobileSiteDir') : '/') + 'mobile/index.php?mobile_action=get_likes&RATING_VOTE_TYPE_ID=' + BXRL[likeId].entityTypeId + '&RATING_VOTE_ENTITY_ID=' + BXRL[likeId].entityId + '&URL=' + BX.message('RVCPathToUserProfile'),
			markmode: false,
			showtitle: false,
			modal: false,
			cache: false,
			outsection: false,
			cancelname: BX.message('RVCListBack')
		});
	}

	return false;
}

RatingLikeComments.LiveUpdate = function(params)
{
	if (params.USER_ID == BX.message('USER_ID'))
	{
		return false;
	}

	for(var i in BXRL)
	{
		if (
			BXRL[i].entityTypeId == params.ENTITY_TYPE_ID
			&& BXRL[i].entityId == params.ENTITY_ID
		)
		{
			oMSL.onLogCommentRatingLike({
				ratingId: i,
				voteAction: (params.TYPE == 'ADD' ? 'plus' : 'cancel'),
				userId: params.USER_ID
			});
		}
	}
}
