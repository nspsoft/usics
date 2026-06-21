import{s as k,g as R,t as F,q as I,a as _,b as E,_ as l,K as D,z as G,F as y,G as z,H as C,l as P,a1 as H}from"./Index-D5aafDlM.js";import{p as V}from"./chunk-4BX2VUAB-BCVDvD-g.js";import{p as W}from"./treemap-KMMF4GRG-xDfwMBkD.js";import"./app-Db33n_ou.js";/* empty css            */import"./AppLayout-B40PkUEl.js";import"./HomeIcon-BUB4aZ_O.js";import"./_plugin-vue_export-helper-DlAUqK2U.js";import"./XMarkIcon-JRIfyUA9.js";import"./WrenchScrewdriverIcon-Cy17HcUw.js";import"./SunIcon-CPn4Z509.js";import"./CheckCircleIcon-DvQn9_NY.js";import"./ShieldExclamationIcon-CxxffeBV.js";import"./UserPlusIcon-C3LIQN_V.js";import"./TruckIcon-Com9u3h3.js";import"./UserGroupIcon-QATQVUQu.js";import"./DocumentTextIcon-DS9SLfQ7.js";import"./ShoppingCartIcon-B8ocyTXV.js";import"./MapPinIcon-D4Ygn01H.js";import"./SparklesIcon-CyXVGo_r.js";import"./DocumentPlusIcon-Dfg4KtGg.js";import"./CubeIcon-rrpx7EN5.js";import"./ClockIcon-31ppPzlX.js";import"./ArrowPathIcon-rbZg8S3E.js";import"./CalendarIcon-Cn06fsKo.js";import"./ShieldCheckIcon-D2JgN4v7.js";import"./BeakerIcon-BcH2NA97.js";import"./DocumentCheckIcon-CmUCAyJW.js";import"./min-qd5qtytq.js";import"./_baseUniq-DiCH2LMQ.js";var h={showLegend:!0,ticks:5,max:null,min:0,graticule:"circle"},w={axes:[],curves:[],options:h},g=structuredClone(w),B=z.radar,j=l(()=>y({...B,...C().radar}),"getConfig"),b=l(()=>g.axes,"getAxes"),q=l(()=>g.curves,"getCurves"),K=l(()=>g.options,"getOptions"),N=l(a=>{g.axes=a.map(t=>({name:t.name,label:t.label??t.name}))},"setAxes"),U=l(a=>{g.curves=a.map(t=>({name:t.name,label:t.label??t.name,entries:X(t.entries)}))},"setCurves"),X=l(a=>{if(a[0].axis==null)return a.map(e=>e.value);const t=b();if(t.length===0)throw new Error("Axes must be populated before curves for reference entries");return t.map(e=>{const r=a.find(s=>s.axis?.$refText===e.name);if(r===void 0)throw new Error("Missing entry for axis "+e.label);return r.value})},"computeCurveEntries"),Y=l(a=>{const t=a.reduce((e,r)=>(e[r.name]=r,e),{});g.options={showLegend:t.showLegend?.value??h.showLegend,ticks:t.ticks?.value??h.ticks,max:t.max?.value??h.max,min:t.min?.value??h.min,graticule:t.graticule?.value??h.graticule}},"setOptions"),Z=l(()=>{G(),g=structuredClone(w)},"clear"),$={getAxes:b,getCurves:q,getOptions:K,setAxes:N,setCurves:U,setOptions:Y,getConfig:j,clear:Z,setAccTitle:E,getAccTitle:_,setDiagramTitle:I,getDiagramTitle:F,getAccDescription:R,setAccDescription:k},J=l(a=>{V(a,$);const{axes:t,curves:e,options:r}=a;$.setAxes(t),$.setCurves(e),$.setOptions(r)},"populate"),Q={parse:l(async a=>{const t=await W("radar",a);P.debug(t),J(t)},"parse")},tt=l((a,t,e,r)=>{const s=r.db,o=s.getAxes(),i=s.getCurves(),n=s.getOptions(),c=s.getConfig(),p=s.getDiagramTitle(),m=D(t),d=et(m,c),u=n.max??Math.max(...i.map(f=>Math.max(...f.entries))),x=n.min,v=Math.min(c.width,c.height)/2;at(d,o,v,n.ticks,n.graticule),rt(d,o,v,c),M(d,o,i,x,u,n.graticule,c),T(d,i,n.showLegend,c),d.append("text").attr("class","radarTitle").text(p).attr("x",0).attr("y",-c.height/2-c.marginTop)},"draw"),et=l((a,t)=>{const e=t.width+t.marginLeft+t.marginRight,r=t.height+t.marginTop+t.marginBottom,s={x:t.marginLeft+t.width/2,y:t.marginTop+t.height/2};return a.attr("viewbox",`0 0 ${e} ${r}`).attr("width",e).attr("height",r),a.append("g").attr("transform",`translate(${s.x}, ${s.y})`)},"drawFrame"),at=l((a,t,e,r,s)=>{if(s==="circle")for(let o=0;o<r;o++){const i=e*(o+1)/r;a.append("circle").attr("r",i).attr("class","radarGraticule")}else if(s==="polygon"){const o=t.length;for(let i=0;i<r;i++){const n=e*(i+1)/r,c=t.map((p,m)=>{const d=2*m*Math.PI/o-Math.PI/2,u=n*Math.cos(d),x=n*Math.sin(d);return`${u},${x}`}).join(" ");a.append("polygon").attr("points",c).attr("class","radarGraticule")}}},"drawGraticule"),rt=l((a,t,e,r)=>{const s=t.length;for(let o=0;o<s;o++){const i=t[o].label,n=2*o*Math.PI/s-Math.PI/2;a.append("line").attr("x1",0).attr("y1",0).attr("x2",e*r.axisScaleFactor*Math.cos(n)).attr("y2",e*r.axisScaleFactor*Math.sin(n)).attr("class","radarAxisLine"),a.append("text").text(i).attr("x",e*r.axisLabelFactor*Math.cos(n)).attr("y",e*r.axisLabelFactor*Math.sin(n)).attr("class","radarAxisLabel")}},"drawAxes");function M(a,t,e,r,s,o,i){const n=t.length,c=Math.min(i.width,i.height)/2;e.forEach((p,m)=>{if(p.entries.length!==n)return;const d=p.entries.map((u,x)=>{const v=2*Math.PI*x/n-Math.PI/2,f=A(u,r,s,c),O=f*Math.cos(v),S=f*Math.sin(v);return{x:O,y:S}});o==="circle"?a.append("path").attr("d",L(d,i.curveTension)).attr("class",`radarCurve-${m}`):o==="polygon"&&a.append("polygon").attr("points",d.map(u=>`${u.x},${u.y}`).join(" ")).attr("class",`radarCurve-${m}`)})}l(M,"drawCurves");function A(a,t,e,r){const s=Math.min(Math.max(a,t),e);return r*(s-t)/(e-t)}l(A,"relativeRadius");function L(a,t){const e=a.length;let r=`M${a[0].x},${a[0].y}`;for(let s=0;s<e;s++){const o=a[(s-1+e)%e],i=a[s],n=a[(s+1)%e],c=a[(s+2)%e],p={x:i.x+(n.x-o.x)*t,y:i.y+(n.y-o.y)*t},m={x:n.x-(c.x-i.x)*t,y:n.y-(c.y-i.y)*t};r+=` C${p.x},${p.y} ${m.x},${m.y} ${n.x},${n.y}`}return`${r} Z`}l(L,"closedRoundCurve");function T(a,t,e,r){if(!e)return;const s=(r.width/2+r.marginRight)*3/4,o=-(r.height/2+r.marginTop)*3/4,i=20;t.forEach((n,c)=>{const p=a.append("g").attr("transform",`translate(${s}, ${o+c*i})`);p.append("rect").attr("width",12).attr("height",12).attr("class",`radarLegendBox-${c}`),p.append("text").attr("x",16).attr("y",0).attr("class","radarLegendText").text(n.label)})}l(T,"drawLegend");var st={draw:tt},nt=l((a,t)=>{let e="";for(let r=0;r<a.THEME_COLOR_LIMIT;r++){const s=a[`cScale${r}`];e+=`
		.radarCurve-${r} {
			color: ${s};
			fill: ${s};
			fill-opacity: ${t.curveOpacity};
			stroke: ${s};
			stroke-width: ${t.curveStrokeWidth};
		}
		.radarLegendBox-${r} {
			fill: ${s};
			fill-opacity: ${t.curveOpacity};
			stroke: ${s};
		}
		`}return e},"genIndexStyles"),ot=l(a=>{const t=H(),e=C(),r=y(t,e.themeVariables),s=y(r.radar,a);return{themeVariables:r,radarOptions:s}},"buildRadarStyleOptions"),it=l(({radar:a}={})=>{const{themeVariables:t,radarOptions:e}=ot(a);return`
	.radarTitle {
		font-size: ${t.fontSize};
		color: ${t.titleColor};
		dominant-baseline: hanging;
		text-anchor: middle;
	}
	.radarAxisLine {
		stroke: ${e.axisColor};
		stroke-width: ${e.axisStrokeWidth};
	}
	.radarAxisLabel {
		dominant-baseline: middle;
		text-anchor: middle;
		font-size: ${e.axisLabelFontSize}px;
		color: ${e.axisColor};
	}
	.radarGraticule {
		fill: ${e.graticuleColor};
		fill-opacity: ${e.graticuleOpacity};
		stroke: ${e.graticuleColor};
		stroke-width: ${e.graticuleStrokeWidth};
	}
	.radarLegendText {
		text-anchor: start;
		font-size: ${e.legendFontSize}px;
		dominant-baseline: hanging;
	}
	${nt(t,e)}
	`},"styles"),zt={parser:Q,db:$,renderer:st,styles:it};export{zt as diagram};
