import{b as g,d as w,r as M,o as V,c as C,e as t,w as I,a as n,u as a,g as U,n as $,f as y}from"./app-DaLdgCdF.js";import{u as k,E as i,a as u,_ as E,r as d,e as x}from"./BaseBtn.vue_vue_type_script_setup_true_lang-DXLPtLmw.js";import{s as N,m as p}from"./toast-notificaltion-BEiIHqqv.js";import{s as O}from"./utils-DqYZlPan.js";import{m as r}from"./MemberStore-BMAg7JUi.js";import"./_plugin-vue_export-helper-DlAUqK2U.js";function R(){const s=g(!1);async function l(){try{s.value=!0;const o=r.edit?await B():await S();s.value=!1,r.memberInput={},N(o.message)}catch(o){s.value=!1,O(o)}}return{createOrUpdate:l,loading:s}}async function S(){return await p("members","POST",r.memberInput)}async function B(){const s=await p("members","PUT",r.memberInput);return r.edit=!1,s}const T={class:"container"},h={class:"row"},q={class:"col-md-6"},L={class:"form-group"},P={class:"form-group"},z={class:"form-group"},H={class:"form-group"},j={class:"form-group mt-3"},Q=w({__name:"CreateMember",setup(s){const o=k({email:{required:d,email:x},name:{required:d}},r.memberInput),{loading:c,createOrUpdate:b}=R();async function f(){await o.value.$validate()&&(await b(),o.value.$reset())}return(_,e)=>{const v=M("RouterLink");return V(),C("div",T,[t("div",h,[t("div",q,[e[4]||(e[4]=t("h3",null,"Create Member",-1)),e[5]||(e[5]=t("br",null,null,-1)),t("form",{onSubmit:I(f,["prevent"])},[t("div",L,[t("div",P,[n(i,{label:"Name",errors:a(o).name.$errors},null,8,["errors"]),n(u,{modelValue:a(r).memberInput.name,"onUpdate:modelValue":e[0]||(e[0]=m=>a(r).memberInput.name=m)},null,8,["modelValue"])])]),t("div",z,[t("div",H,[n(i,{label:"Email",errors:a(o).email.$errors},null,8,["errors"]),n(u,{modelValue:a(r).memberInput.email,"onUpdate:modelValue":e[1]||(e[1]=m=>a(r).memberInput.email=m)},null,8,["modelValue"])])]),e[3]||(e[3]=t("br",null,null,-1)),n(v,{to:"/members"},{default:U(()=>e[2]||(e[2]=[y("See members list")])),_:1}),t("div",j,[n(E,{class:$(a(r).edit?"btn btn-warning":"btn btn-primary"),label:a(r).edit?"Update Membre":"Create Member",loading:a(c)},null,8,["class","label","loading"])])],32)])])])}}});export{Q as default};
