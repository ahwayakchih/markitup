mySettings = {
	previewParserPath:	'',
	onShiftEnter:	{keepDefault:false, openWith:'  \n'},
	onCtrlEnter:	{keepDefault:false, openWith:'\n\n'},
	onTab:			{keepDefault:false, openWith:'	'},
	markupSet: [
		{name:'First Level Heading', className:'h1', key:'1', placeHolder:'Your title here...', closeWith:function(markItUp) { return '\n' + Array(jQuery.trim(markItUp.selection||markItUp.placeHolder).length+1).join('=') } },
		{name:'Second Level Heading', className:'h2', key:'2', placeHolder:'Your title here...', closeWith:function(markItUp) { return '\n' + Array(jQuery.trim(markItUp.selection||markItUp.placeHolder).length+1).join('-') } },
		{name:'Heading 3', key:'3', className:'h3', openWith:'### ', placeHolder:'Your title here...' },
		{name:'Heading 4', key:'4', className:'h4', openWith:'#### ', placeHolder:'Your title here...' },
		{name:'Heading 5', key:'5', className:'h5', openWith:'##### ', placeHolder:'Your title here...' },
		{name:'Heading 6', key:'6', className:'h6', openWith:'###### ', placeHolder:'Your title here...' },
		{separator:'---------------' },		
		{name:'Bold', key:'B', className:'b', openWith:'**', closeWith:'**'},
		{name:'Italic', key:'I', className:'i', openWith:'_', closeWith:'_'},
		{separator:'---------------' },
		{name:'Bulleted List', className:'ul', openWith:'- ' },
		{name:'Numeric List', className:'ol', openWith:function(markItUp) {
			return markItUp.line+'. ';
		}},
		{separator:'---------------' },
		{name:'Link', key:'L', className:'a', openWith:'[', closeWith:']([![Url:!:http://]!](!( "[![Title]!]")!))', placeHolder:'Your text to link here...' },
		{separator:'---------------'},	
		{name:'Quotes', openWith:'> ', className:'blockquote', },
		{name:'Code Block / Code', className:'code', openWith:'(!(\t|!|`)!)', closeWith:'(!(`)!)'}
	]
}

jQuery(document).ready(function($) {
	$('.markdown').markItUp(mySettings);
});
