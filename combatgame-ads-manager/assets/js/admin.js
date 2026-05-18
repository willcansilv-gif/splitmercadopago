(async function(){
  const canvas = document.getElementById('cgam-chart');
  if(!canvas || typeof Chart==='undefined') return;
  const req = await fetch(cgamData.rest, {headers:{'X-WP-Nonce': cgamData.nonce}});
  const data = await req.json();
  new Chart(canvas, {type:'bar',data:{labels:['Impressões','Cliques','CTR'],datasets:[{label:'Hoje',data:[data.impressions||0,data.clicks||0,data.ctr||0],backgroundColor:['#22d3ee','#a78bfa','#34d399']}]},options:{responsive:true}});
})();
