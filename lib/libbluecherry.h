/*
 * Copyright (C) 2010 Bluecherry, LLC
 *
 * Confidential, all rights reserved. No distribution is permitted.
 */

#ifndef __LIBBLUECHERRY_H
#define __LIBBLUECHERRY_H

#include <linux/videodev2.h>

#define BC_MAX_BUFFERS		32

struct bc_handle {
	/* Track info about the v4l2 device */
	char			dev_file[256];
	int			dev_fd;
	struct v4l2_format	vfmt;
	struct v4l2_capability	vcap;
	struct v4l2_streamparm	vparm;
	/* Userspace buffer accounting */
	struct {
		void *data;
		size_t size;
	}			p_buf[BC_MAX_BUFFERS];
	int			p_cnt;
	struct v4l2_buffer	q_buf[BC_MAX_BUFFERS >> 1];
	int			q_cnt;
	int			cur_q_buf;
};

/* Called to open and close a handle for a device. */
struct bc_handle *bc_handle_get(const char *dev);
void bc_handle_free(struct bc_handle *bc);

/* Called to start/stop the stream */
int bc_handle_start(struct bc_handle *bc);
void bc_handle_stop(struct bc_handle *bc);

/* Retrieves the next buffer from the device */
int bc_buf_get(struct bc_handle *bc);

/* Get the data pointer for the current buffer */
static inline void *bc_buf_data(struct bc_handle *bc)
{
	if (bc->q_cnt == 0)
		return NULL;
	return bc->p_buf[bc->q_buf[bc->q_cnt - 1].index].data;
}

/* Get the size in bytes used by the current buffer */
static inline size_t bc_buf_size(struct bc_handle *bc)
{
        if (bc->q_cnt == 0)
                return 0;
        return bc->q_buf[bc->q_cnt - 1].bytesused;
}

#endif /* __LIBBLUECHERRY_H */
