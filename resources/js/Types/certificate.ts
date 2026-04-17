export interface CertificateType {
    id: number;
    enrollmentId: number;
    userId: number;
    courseId: number;
    verificationCode: string;
    learnerName: string;
    courseTitle: string;
    completedAt: string;
    pdfPath: string | null;
}
