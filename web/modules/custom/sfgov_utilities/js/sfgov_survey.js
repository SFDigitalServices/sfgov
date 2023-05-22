(() => {
  const surveyBtn = document.querySelector('.survey-btn');
  surveyBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    e.preventDefault();
    // doing it like this only allows the survey to show up once.  it's probably cookied or something
    (function(t,e,s,o){var n,c,l;t.SMCX=t.SMCX||[],e.getElementById(o)||(n=e.getElementsByTagName(s),c=n[n.length-1],l=e.createElement(s),l.type="text/javascript",l.async=!0,l.id=o,l.src="https://widget.surveymonkey.com/collect/website/js/tRaiETqnLgj758hTBazgd3fGeVIeoaJmQrI93lw65EJe1FBHzsXXKMBGUYuf7LFm.js",c.parentNode.insertBefore(l,c))})(window,document,"script","smcx-sdk");
  })
})();