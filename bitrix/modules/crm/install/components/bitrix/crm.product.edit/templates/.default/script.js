function addNewTableRow(tableID, col_count, regexp, rindex)
{
	var tbl = document.getElementById(tableID);
	var cnt = tbl.rows.length;
	var oRow = tbl.insertRow(cnt);

	for(var i=0;i<col_count;i++)
	{
		var oCell = oRow.insertCell(i);
		var html = tbl.rows[cnt-1].cells[i].innerHTML;
		oCell.innerHTML = html.replace(regexp,
			function(html)
			{
				return html.replace('[n'+arguments[rindex]+']', '[n'+(1+parseInt(arguments[rindex]))+']');
			}
		);
	}
}
