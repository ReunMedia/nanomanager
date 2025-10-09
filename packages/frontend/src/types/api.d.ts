/**
 * Fronted types for API requests and responses
 *
 * Types in this file must match ones in `packages/php/phpstan.dist.neon`
 */

import type { EmptyObject } from "type-fest";

export type OperationType =
  | "deleteFile"
  | "listFiles"
  | "renameFile"
  | "uploadFile";

export interface operation_listFiles extends Operation {
  parameters: EmptyObject;
  result: {
    data: {
      /**
       * List of filenames
       */
      files: string[];
      /**
       * Base URL used when linking to files
       */
      baseUrl: string;
    };
  };
}

export interface operation_renameFile extends Operation {
  parameters: {
    oldName: string;
    newName: string;
  };
  result: {
    data: {
      /**
       * New name of the file. If the renaming failed, the original file name
       * is returned.
       */
      newName: string;
    };
  };
}

export interface operation_deleteFile extends Operation {
  parameters: {
    filename: string;
  };
  result: {
    data: {
      success: boolean;
    };
  };
}

export interface operation_uploadFile extends Operation {
  parameters: {
    // NOTE - This differs fron `phpstan.dist.neon` parameters because file
    // uploading is a special case that is handled separately.
    files: FileList;
  };
  result: {
    data: {
      /**
       * List of files that were successfully uploaded.
       */
      uploadedFiles: string[];
      /**
       * List of files that couldn't be uploaded.
       */
      filesWithErrors: string[];
    };
  };
}

//
// Additional frontend-only types below
//

interface Operation {
  /**
   * Input parameters of the operation
   */
  parameters: object;
  /**
   * Operation result
   */
  result: {
    /**
     * Returned data
     */
    data: Record<string, unknown>;
  };
}
