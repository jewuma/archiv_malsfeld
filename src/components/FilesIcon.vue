<template>
  <div v-if="item && item.count > 0" class="position-relative d-inline-block">
    <button class="btn btn-link p-0" @click.stop="onClick" :title="tooltip">
      <i :class="iconClass" class="fs-3"></i>
    </button>
    <span v-if="item.count > 1" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary"
      style="font-size:0.65rem">
      {{ item.count }}
    </span>
  </div>
</template>

<script>
export default {

  name: "FilesIcon",

  /**
   * Displays attached files inside a table cell.
   *
   * Props
   * -----
   * id
   *   Number | String
   *   Identifier of the table row.
   *
   * item
   *   {
   *      count: Number,            // Number of attached files
   *      firstId: Number|null,     // Id of the first file
   *      firstType: Array<String>|null  // Optional type of the first file
   *   }
   *
   * Behaviour
   * ---------
   * 0 files:
   *   Nothing is displayed.
   *
   * 1 file:
   *   Opens the file directly.
   *
   * >1 files:
   *   Emits "show-files". A later dialog can display all attached files.
   */

  props: {

    item: {
      type: Object,
      required: true
    },
  },

  emits: [
    "show-files"
  ],

  computed: {

    iconClass() {

      const type = this.item.type || [];

      if (["jpg", "jpeg", "png", "gif", "bmp", "tif", "tiff", "webp"].includes(type))
        return "bi bi-image text-success";

      if (type === "pdf")
        return "bi bi-file-earmark-pdf-fill text-danger";

      if (["mp4", "avi", "mov", "mkv", "webm"].includes(type))
        return "bi bi-film text-primary";

      return "bi bi-files";
    },

    tooltip() {

      if (this.item.count === 1)
        return "Open file";

      return `${this.item.count} files`;
    }

  },

  methods: {

    onClick() {

      if (this.item.count === 1) {
        this.openFile(this.item.firstId);
      }
      else {
        this.$emit("show-files", this.id);
      }

    },

    async openFile(fileId) {

      try {

        const response = await this.$axios.get(
          `/ArchivFiles/getBrowserCompatible/${fileId}`,
          {
            responseType: "blob"
          }
        );

        const url = window.URL.createObjectURL(response.data);

        window.open(url, "_blank");

      }
      catch (err) {
        console.error(err);
      }

    }

  }

};
</script>