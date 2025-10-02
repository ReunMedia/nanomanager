import type { EmptyObject, OmitIndexSignature } from "type-fest";

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

const apiUrl = "http://localhost:8080";

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
