function jsTypeChanged(form_id, dropdown)
{
	var _form = BX(form_id);
	var _flag = BX('action');
	if (_form && _flag)
	{
		BX.showWait();
		_flag.value = 'type_changed';
		_form.submit();
	}
}

var max_sort;

function addNewTableRow(tableID, regexp, rindex)
{
	var tbl = BX(tableID);
	var cnt = tbl.rows.length;
	var oRow = tbl.insertRow(cnt);
	var col_count = tbl.rows[cnt - 1].cells.length;

	if (!max_sort)
	{
		var inpSort = BX.findChild(tbl.rows[cnt - 1], {'tag': 'input', 'class': 'sort-input'}, true);
		if (inpSort)
			max_sort = parseInt(inpSort.value) + 10;
	}

	for (var i = 0; i < col_count; i++)
	{
		var oCell = oRow.insertCell(i);
		var html = tbl.rows[cnt - 1].cells[i].innerHTML;
		oCell.align = tbl.rows[cnt - 1].cells[i].align;
		if (i == 0)
			oCell.style.display = 'none';
		else
			oCell.className = tbl.rows[cnt - 1].cells[i].className;
		oCell.innerHTML = html.replace(regexp,
			function (html)
			{
				return html.replace('[n' + arguments[rindex] + ']', '[n' + (1 + parseInt(arguments[rindex])) + ']');
			}
		);
	}

	var newSort = BX.findChild(tbl.rows[cnt], {'tag': 'input', 'class': 'sort-input'}, true);
	if (newSort)
	{
		newSort.value = max_sort;
		max_sort += 10;
	}
}

function jsDelete(form_id, message)
{
	var _form = BX(form_id);
	var _flag = BX('action');
	if (_form && _flag)
	{
		if (confirm(message))
		{
			_flag.value = 'delete';
			_form.submit();
		}
	}
}

function delete_item(button)
{
	var tableRow = BX.findParent(button, {'tag': 'tr'});
	if (tableRow)
	{
		var hidden = BX.findChild(tableRow, {'tag': 'input', 'class': 'sort-input'}, true);
		if (hidden)
		{
			var table = tableRow.parentNode;
			table.parentNode.appendChild(hidden);
			table.removeChild(tableRow);
		}
	}
}

function toggle_input(input_id)
{
	var _input = BX(input_id);
	if (_input)
	{
		if (_input.style.display == 'block')
			_input.style.display = 'none';
		else
			_input.style.display = 'block';
	}
}

/* A function for moving the rows in the list */
var dragTable = function (table, callbacks)
{
	var dragTr = false;
	var tbody = false;
	var startY = false;
	var indexStart = false;
	var trStart = false;
	var init = function ()
	{
		tbody = table.getElementsByTagName('tbody')[0];
		table.onmousedown = start;
		table.onmouseleave = stop;
		table.onmouseup = stop;
		table.onmousemove = move;
	}

	var start = function (e)
	{
		var target = e.target || e.srcElement;
		if (target.tagName == 'TD')
		{
			BX.eventReturnFalse(e);
			var tr = target.parentNode;
			if (tr.parentNode.nodeName !== 'TBODY' && target.tagName == "TD") return false;
			if (dragTr && target.tagName == "TD") return false;
			trStart = tr;
			dragTr = tr;
			dragTr.setAttribute('class', 'lists-field-drag-tr');
			startY = e.y || e.clientY;
			indexStart = __getIndex(dragTr)
			if (callbacks && callbacks.start) callbacks.start(table, trStart, indexStart);
		}

	}

	var stop = function (e)
	{
		var target = e.target || e.srcElement;
		if (target.tagName == 'TD')
		{
			if (!dragTr) return false;
			dragTr.removeAttribute('class');
			startY = false;
			dragTr = false;
			if (callbacks && callbacks.stop) callbacks.stop(table, trStart, indexStart, __getIndex(trStart));
		}
	}

	var move = function (e)
	{
		var target = e.target || e.srcElement;
		if (target.tagName == 'TD')
		{
			if (!dragTr) return false;
			var currentTr = target.parentNode;
			if (currentTr === dragTr || currentTr.nodeName !== 'TR' || currentTr.parentNode.nodeName !== 'TBODY') return false;
			var y = e.y || e.clientY;
			var top = y < startY;
			startY = y;
			if (top)
			{
				tbody.insertBefore(dragTr, currentTr);
			}
			else
			{
				tbody.insertBefore(currentTr, dragTr);
			}
			if (callbacks && callbacks.dragging) callbacks.dragging(table, dragTr, currentTr, __getIndex(trStart))
		}
	}

	var __getIndex = function (tr)
	{
		var trs = tbody.getElementsByTagName('tr');
		for (var i = 0, length = trs.length; i < length; i++)
		{
			if (trs[i] === tr) return (i + 1);
		}
		return 0;
	}
	init();
}

function enumerationValues(table)
{
	var listValue = BX.findChildren(table, {"tag": "input", "attribute": {"type": "hidden"}}, true);
	if (listValue[listValue.length - 1].getAttribute('name') == "LIST[n0][SORT]")
	{
		for (var i = 1; i <= listValue.length; i++)
		{
			listValue[i - 1].setAttribute('value', i * 10);
		}
	}
}