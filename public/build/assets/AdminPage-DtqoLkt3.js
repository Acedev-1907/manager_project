import{d as b,b as _,o as i,c as r,e,u as m,f as d,t as f,F as w,h,a as c,g,n as x,R as U,i as L,r as $,T as C,j as T,k as j}from"./app-CADoG00p.js";import{A as B,m as E,g as I}from"./makeHttpReq-IEoXqM35.js";const M={id:"sidebarMenu",style:{"background-color":"white"},class:"col-md-3 col-lg-2 d-md-block sidebar collapse"},N={class:"position-sticky pt-3"},P={align:"center"},A=["src"],R={class:"nav flex-column"},D=b({__name:"NarBar",props:{loggedInUserEmail:{}},emits:["logout"],setup(n,{emit:a}){const o=_([{name:"Dashboard",link:"/admin",icon:"bi bi-wrench-adjustable"},{name:"Projects",link:"/projects",icon:"bi bi-file-ppt"},{name:"Members",link:"/members",icon:"bi bi-file-ppt"}]),l=a;return(u,t)=>(i(),r("nav",M,[e("div",N,[e("div",P,[e("img",{src:`${m(B).baseURL}/others/logo.png`,style:{height:"55px"},alt:""},null,8,A),t[1]||(t[1]=e("h4",null,"TaskMgr",-1)),d(" "+f(u.loggedInUserEmail),1)]),t[3]||(t[3]=e("br",null,null,-1)),t[4]||(t[4]=e("h6",{class:"sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted"},[e("span",{style:{color:"red"}},"Menu"),e("a",{class:"link-secondary",href:"#","aria-label":"Add a new report"},[e("span",{"data-feather":"plus-circle"})])],-1)),e("ul",R,[(i(!0),r(w,null,h(o.value,s=>(i(),r("li",{class:"nav-item",key:s.name},[c(m(U),{class:"nav-link",to:s.link,exact:""},{default:g(()=>[e("i",{class:x(s.icon)},null,2),d(" "+f(s.name),1)]),_:2},1032,["to"])]))),128)),e("li",{class:"nav-item",style:{cursor:"pointer"},onClick:t[0]||(t[0]=s=>l("logout"))},t[2]||(t[2]=[e("a",{class:"nav-link"},[e("i",{class:"bi bi-box-arrow-right"}),d(" Logout ")],-1)]))])])]))}});function S(){const n=_(!1);async function a(o){try{n.value=!0;const l=await E("logout","POST",{userId:o});n.value=!1}catch(l){n.value=!1,l.message=="Not authenticated"&&(window.location.href="/app/login")}}return{logout:a,loading:n}}const V={class:"container"},F={class:"container-fluid"},O={class:"row"},q={class:"col-md-9 ms-sm-auto col-lg-10 bg-pages"},G=b({__name:"AdminPage",setup(n){const{logout:a}=S(),o=I();async function l(){var s;const t=(s=o==null?void 0:o.user)==null?void 0:s.id;typeof t<"u"&&(await a(t),localStorage.clear(),setTimeout(()=>window.location.href="/app/login",1e3))}async function u(){await a(void 0)}return L(async()=>{await u()}),(t,s)=>{var p;const k=$("router-view");return i(),r("div",V,[e("div",F,[e("div",O,[c(D,{loggedInUserEmail:(p=m(o))==null?void 0:p.user.email,onLogout:l},null,8,["loggedInUserEmail"]),e("main",q,[s[0]||(s[0]=e("br",null,null,-1)),s[1]||(s[1]=e("br",null,null,-1)),c(k,null,{default:g(({Component:v,route:y})=>[c(C,{name:"fade",mode:"out-in"},{default:g(()=>[(i(),r("div",{key:y.name},[(i(),T(j(v)))]))]),_:2},1024)]),_:1})])])])])}}});export{G as default};
