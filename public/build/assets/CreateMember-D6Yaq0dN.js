import{b as I,d as M,r as m,o as V,c as B,e as t,w as C,a as n,u as s,g as U,n as y,f as k}from"./app-CADoG00p.js";import{u as E,r as p,e as $}from"./index-B_rufATu.js";import{m as c}from"./makeHttpReq-IEoXqM35.js";import{s as x}from"./toast-notificaltion-Cc3Mv6Zn.js";import{s as N}from"./utils-Dc91b2uC.js";import{m as r}from"./MemberStore-CNmS-ugz.js";function O(){const a=I(!1);async function u(){try{a.value=!0;const o=r.edit?await S():await R();a.value=!1,r.memberInput={},x(o.message)}catch(o){a.value=!1,N(o)}}return{createOrUpdate:u,loading:a}}async function R(){return await c("members","POST",r.memberInput)}async function S(){const a=await c("members","PUT",r.memberInput);return r.edit=!1,a}const T={class:"container"},h={class:"row"},q={class:"col-md-6"},L={class:"form-group"},P={class:"form-group"},z={class:"form-group"},H={class:"form-group"},j={class:"form-group mt-3"},Q=M({__name:"CreateMember",setup(a){const o=E({email:{required:p,email:$},name:{required:p}},r.memberInput),{loading:b,createOrUpdate:f}=O();async function _(){await o.value.$validate()&&(await f(),o.value.$reset())}return(v,e)=>{const i=m("Error"),d=m("BaseInput"),g=m("RouterLink"),w=m("BaseBtn");return V(),B("div",T,[t("div",h,[t("div",q,[e[4]||(e[4]=t("h3",null,"Create Member",-1)),e[5]||(e[5]=t("br",null,null,-1)),t("form",{onSubmit:C(_,["prevent"])},[t("div",L,[t("div",P,[n(i,{label:"Name",errors:s(o).name.$errors},null,8,["errors"]),n(d,{modelValue:s(r).memberInput.name,"onUpdate:modelValue":e[0]||(e[0]=l=>s(r).memberInput.name=l)},null,8,["modelValue"])])]),t("div",z,[t("div",H,[n(i,{label:"Email",errors:s(o).email.$errors},null,8,["errors"]),n(d,{modelValue:s(r).memberInput.email,"onUpdate:modelValue":e[1]||(e[1]=l=>s(r).memberInput.email=l)},null,8,["modelValue"])])]),e[3]||(e[3]=t("br",null,null,-1)),n(g,{to:"/members"},{default:U(()=>e[2]||(e[2]=[k("See members list")])),_:1}),t("div",j,[n(w,{class:y(s(r).edit?"btn btn-warning":"btn btn-primary"),label:s(r).edit?"Update Membrer":"Create Member",loading:s(b)},null,8,["class","label","loading"])])],32)])])])}}});export{Q as default};
