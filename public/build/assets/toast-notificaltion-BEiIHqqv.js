import{N as u}from"./app-DaLdgCdF.js";const d={baseURL:"http://127.0.0.1:8000",apiBaseURL:"http://127.0.0.1:8000/api"};function h(){try{const t=localStorage.getItem("userData");if(typeof t!="object")return JSON.parse(t)}catch(t){console.log(t.message)}}function f(t,o,i){return new Promise(async(c,e)=>{try{const s=h(),p="Bearer "+(s==null?void 0:s.token),a=await fetch(`${d.apiBaseURL}/${t}`,{method:o,headers:{"content-type":"application/json",Authorization:p},body:JSON.stringify(i)}),r=await a.json();a.ok||e(r),c(r)}catch(s){e(s)}})}const n=u.useToast();function g(t){n.error(t,{position:"bottom-right",duration:4e3,dismissible:!0})}function b(t){n.success(t,{position:"bottom-right",duration:4e3,dismissible:!0})}export{d as A,g as a,h as g,f as m,b as s};
