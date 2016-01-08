var diskufPopup = false;
var diskufCurrentIdDocument = false;
var diskufCurrentFileDialog = false;
var diskufMenuNumber = 0;
function DiskActionFileMenu(id, bindElement, buttons)

{
	diskufMenuNumber++;
	BX.PopupMenu.show('bx-viewer-wd-popup' + diskufMenuNumber + '_' + id, BX(bindElement), buttons,
		{
			angle: {
				position: 'top',
				offset: 25
			},
			autoHide: true
		}
	);

	return false;
}
/**
 * Forward click event from inline element to main element (with additional properties)
 * @param element
 * @param realElementId main element (in attached block)
 * @returns {boolean}
 * @constructor
 */
function WDInlineElementClickDispatcher(element, realElementId)
{
	var realElement = BX(realElementId);
	if(realElement)
	{
		BX.fireEvent(realElement, 'click');
	}
	return false;
}

function showWebdavHistoryPopup(historyUrl, docId, bindElement)
{
	bindElement = bindElement || null;
	if(diskufPopup)
	{
		diskufPopup.show();
		return;
	}
	if(diskufCurrentIdDocument == docId)
	{
		return;
	}
	diskufCurrentIdDocument = docId;
	diskufPopup = new BX.PopupWindow(
		'bx_webdav_history_popup',
		bindElement,
		{
			closeIcon : true,
			offsetTop: 5,
			autoHide: true,
			zIndex : -100,
			content:
				BX.create('div', {
					children: [
				BX.create('div', {
					style: {
						display: 'table',
						width: '665px',
						height: '225px'
					},
					children: [
						BX.create('div', {
							style: {
								display: 'table-cell',
								verticalAlign: 'middle',
								textAlign: 'center'
							},
							children: [
								BX.create('div', {
									props: {
										className: 'bx-viewer-wrap-loading-modal'
									}
								}),
								BX.create('span', {
									text: ''
								})
							]
						})
					]
				}
			)
					]
				}),
			closeByEsc: true,
			draggable: true,
			titleBar: {content: BX.create("span", { text: BX.message('WDUF_FILE_TITLE_REV_HISTORY')})},
			events : {
				'onPopupClose': function()
				{
					diskufPopup.destroy();
					diskufPopup = diskufCurrentIdDocument = false;
				}
			}
		}
	);
	diskufPopup.show();
	BX.ajax.get(historyUrl, function(data)
	{
		diskufPopup.setContent(BX.create('DIV', {html: data}));
	});
}

