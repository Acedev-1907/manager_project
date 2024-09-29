import{n as B}from"./laravel-vue-pagination.es-1FsGsNxG.js";import{m as C}from"./makeHttpReq-IEoXqM35.js";import{s as D,m as E}from"./utils-DeFJ5rtO.js";import{b as _,d as $,r as h,o as m,c as b,e as t,a as f,u as d,m as R,p as x,F as L,h as S,t as y,z as I,f as P,g as j,i as T,A as q,j as G,x as N}from"./app-CIMN4Q9B.js";import{p as g}from"./projectStore-CrgIw_v8.js";import{s as M}from"./toast-notificaltion-S-mg22Rk.js";function O(){const s=_(!1),r=_({});async function o(a=1,c=""){try{s.value=!0;const l=await C(`projects?query=${c}&page=${a}`,"GET");s.value=!1,r.value=l}catch(l){s.value=!1,D(l)}}return{getProjects:o,projectData:r,loading:s}}const z={class:"row"},F={class:"row"},K={class:"col-md-4"},A={style:{color:"blue"}},H={class:"table table-bordered table-striped"},U={class:"progress",role:"progressbar","aria-label":"Example with label","aria-valuenow":"5","aria-valuemin":"0","aria-valuemax":"100"},J=["onClick"],Q=["onClick"],W=$({__name:"ProjectTable",props:{projects:{},loading:{type:Boolean}},emits:["pinnedProject","editProject","viewProjectDetail","getProject"],setup(s,{emit:r}){const o=r,a=_(""),c=E(async function(){await o("getProject",1,a.value)},100);return(l,e)=>{var i,u;const v=h("BaseInput"),w=h("RouterLink");return m(),b("div",z,[t("div",F,[t("div",K,[f(v,{onKeydown:d(c),modelValue:a.value,"onUpdate:modelValue":e[0]||(e[0]=n=>a.value=n),placeholder:"search..."},null,8,["onKeydown","modelValue"]),R(t("span",A,e[1]||(e[1]=[t("b",null,"Searching....",-1)]),512),[[x,l.loading===!0]])])]),e[5]||(e[5]=t("br",null,null,-1)),e[6]||(e[6]=t("br",null,null,-1)),t("table",H,[e[4]||(e[4]=t("thead",null,[t("tr",{style:{"font-weight":"bold"}},[t("td",{width:"5%"},"ID"),t("td",{width:"30%"},"Title"),t("td",{width:"20%"},"Completion"),t("td",{width:"5%"},"Edit"),t("td",{width:"10%"},"Pinned"),t("td",{width:"15%"},"View")])],-1)),t("tbody",null,[(m(!0),b(L,null,S((u=(i=l.projects)==null?void 0:i.data)==null?void 0:u.data,n=>{var p,k;return m(),b("tr",{key:n.id},[t("td",null,y(n.id),1),t("td",null,y(n.name),1),t("td",null,[t("div",U,[t("div",{class:"progress-bar bg-success",style:I({width:((p=n==null?void 0:n.task_progress)==null?void 0:p.progress)+"%"})},y((k=n==null?void 0:n.task_progress)==null?void 0:k.progress)+" %",5)])]),t("td",null,[t("button",{onClick:V=>o("editProject",n),type:"button",class:"btn btn-outline-primary"},"Edit",8,J)]),t("td",null,[t("button",{onClick:V=>o("pinnedProject",n.id),type:"button",class:"btn btn-light"},e[2]||(e[2]=[P("Pinned "),t("i",{class:"bi bi-activity"},null,-1)]),8,Q)]),t("td",null,[f(w,{class:"btn btn-warning",to:"/kaban?query="+n.slug},{default:j(()=>e[3]||(e[3]=[P("View "),t("i",{class:"bi bi-arrow-right"},null,-1)])),_:2},1032,["to"])])])}),128))])])])}}});function X(){const s=_(!1);async function r(o){try{s.value=!0;const a=await C("projects/pinned","POST",{projectId:o});s.value=!1,M(a.message)}catch(a){s.value=!1,D(a)}}return{pinnendProject:r,loading:s}}const Y={class:"container"},Z={class:"row"},tt={class:"col-md-12"},et={class:"card"},nt={class:"card-header"},st={class:"card-body"},ut=$({__name:"ProjectPage",setup(s){const{getProjects:r,projectData:o,loading:a}=O();async function c(){await r()}const l=q();function e(i){g.projectInput={id:i.id,name:i.name,startDate:i.startDate,endDate:i.endDate},g.edit=!0,l.push("/create-project")}const{pinnendProject:v}=X();async function w(i){await v(i),l.push("/admin")}return T(async()=>{c(),g.edit=!1,g.projectInput={}}),(i,u)=>{const n=h("RouterLink");return m(),b("div",Y,[t("div",Z,[t("div",tt,[t("div",et,[t("div",nt,[u[1]||(u[1]=P("Project ")),f(n,{style:{float:"right"},to:"/create-project",class:"btn btn-primary"},{default:j(()=>u[0]||(u[0]=[P("Create Project ")])),_:1})]),t("div",st,[f(W,{onGetProject:d(r),loading:d(a),onEditProject:e,projects:d(o),onPinnendProject:w},{pagination:j(()=>{var p;return[(p=d(o))!=null&&p.data?(m(),G(d(B),{key:0,data:d(o).data,onPaginationChangePage:d(r)},null,8,["data","onPaginationChangePage"])):N("",!0)]}),_:1},8,["onGetProject","loading","projects"])])])])])])}}});export{ut as default};
