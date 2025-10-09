import type { EmptyObject, OmitIndexSignature } from "type-fest";

/**
 * URL is set by Nanomanager backend in production
 */
// DEVELOPER NOTE - To improve performance, this placeholder is only ever
// replaced once by PHP. This means that the "%NANOMANAGER_API_URL%" placeholder
// must NEVER be used anywhere else in the frontend.
const apiUrl = import.meta.env.DEV
  ? "http://localhost:8080"
  : "%NANOMANAGER_API_URL%";

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

interface Operations {
  [key: string]: Operation;
  listFiles: {
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
  };
  renameFile: {
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
  };
  deleteFile: {
    parameters: {
      filename: string;
    };
    result: {
      data: {
        success: boolean;
      };
    };
  };
  uploadFile: {
    parameters: {
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
  };
}

async function apiRequest<T extends keyof OmitIndexSignature<Operations>>(
  operation: T,
  parameters: Operations[T]["parameters"],
): Promise<Operations[T]["result"]> {
  let body: string | FormData = "";

  // Handle file uploads as `multipart/form-data`
  if (operation === "uploadFile") {
    body = new FormData();

    body.append("operationType", operation);

    for (const file of (parameters as Operations["uploadFile"]["parameters"])
      .files) {
      body.append("files[]", file);
    }
  }
  // All other requests are sent as JSON
  else {
    body = JSON.stringify({
      operationType: operation,
      parameters,
    });
  }

  const response = await fetch(apiUrl, {
    method: "POST",
    body,
  });

  const data: Operations[T]["result"] = await response.json();

  return data;
}

export { apiRequest };
