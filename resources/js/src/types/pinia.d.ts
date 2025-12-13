/* eslint-disable @typescript-eslint/no-unused-vars */
import 'pinia';
import type { PersistOptions } from '../plugins/piniaPersist';
import type { StateTree, _GettersTree } from 'pinia';

declare module 'pinia' {
  export interface DefineStoreOptions<
    _Id extends string,
    S extends StateTree,
    _G extends _GettersTree<S>,
    _A
  > {
    /**
     * Persist store state to storage (localStorage, sessionStorage, or memory)
     */
    persist?: PersistOptions | boolean;
  }
}

